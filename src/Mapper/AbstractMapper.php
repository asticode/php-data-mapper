<?php
/*
 *
 * Author of this code is Geoffroy Aubry => https://github.com/geoffroy-aubry
 *
 */

namespace Asticode\DataMapper\Mapper;

use Aura\Sql\ExtendedPdoInterface;
use Aura\Sql\ExtendedPdo;

/**
 * Mapper's common functions.
 */
abstract class AbstractMapper
{
    use TransactionTrait;

    /**
     * @var ExtendedPdoInterface
     */
    protected $oPdo;

    private $aMap;

    /**
     * List of columns to automatically json_encode/decode.
     * @var array
     */
    protected $aJsonColumns;

    /**
     * Options to use when json_encode().
     * @var int
     * @see http://php.net/manual/en/json.constants.php
     */
    private $iJsonEncodeOptions;

    /**
     * @param ExtendedPdoInterface $oPdo
     */
    public function __construct(ExtendedPdoInterface $oPdo)
    {
        $this->oPdo               = $oPdo;
        $this->iJsonEncodeOptions = JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES;
        $this->aJsonColumns       = [];
        $this->aBinaryColumns     = [];
    }

    public function formatToDb(array &$aParameters)
    {
        foreach ($this->aJsonColumns as $sColumn) {
            if (isset($aParameters[$sColumn])) {
                ksort($aParameters[$sColumn], SORT_STRING);
                $aParameters[$sColumn] = json_encode($aParameters[$sColumn], $this->iJsonEncodeOptions);
            }
        }
        foreach ($this->aBinaryColumns as $sColumn) {
            if (isset($aParameters[$sColumn])) {
                $aParameters[$sColumn] = hex2bin($aParameters[$sColumn]);
            }
        }
    }

    public function formatFromDb(array &$aParameters)
    {
        foreach ($this->aJsonColumns as $sColumn) {
            if (isset($aParameters[$sColumn])) {
                $aParameters[$sColumn] = json_decode($aParameters[$sColumn], true);
            }
        }
        foreach ($this->aBinaryColumns as $sColumn) {
            if (isset($aRecord[$sColumn])) {
                $aParameters[$sColumn] = bin2hex($aParameters[$sColumn]);
            }
        }
    }

    /**
     * Returns a where expression (without WHERE keyword) with placeholders instead of values.
     *
     * @param array $aColumnNames List of column's names.
     * @param string $sSeparator Separator between clauses, e.g. ' AND '.
     * @return string
     */
    public function buildWherePlaceholders(array $aColumnNames, $sSeparator)
    {
        $aQueryWhere = [];
        foreach ($aColumnNames as $sColumnName) {
            $aQueryWhere[] = "`$sColumnName`=:$sColumnName";
        }
        $sQueryWhere = implode($sSeparator, $aQueryWhere);
        return $sQueryWhere;
    }

    public function buildSelectQuery($sEntityName, array $aWhere, $sOrderBy = '', $iLimit = 0, $iOffset = 0)
    {
        $sQueryWhere = $this->buildWherePlaceholders(array_keys($aWhere), ' AND ');
        if ($iLimit > 0) {
            $sQueryLimit = ' LIMIT ' . $iOffset . ',' . $iLimit;
        } elseif ($iOffset > 0) {
            // Huge limit from official MYSQL team :D http://stackoverflow.com/questions/255517/mysql-offset-infinite-rows
            $sQueryLimit = ' LIMIT ' . $iOffset . ',18446744073709551615';
        } else {
            $sQueryLimit = '';
        }
        $sQuery      = "SELECT * FROM `$sEntityName`"
            . (! empty($sQueryWhere) ? ' WHERE ' . $sQueryWhere : '')
            . (! empty($sOrderBy) ? ' ORDER BY ' . $sOrderBy : '')
            . $sQueryLimit;
        return [$sQuery, $aWhere];
    }

    public function buildDeleteQuery($sEntityName, array $aWhere)
    {
        $sQueryWhere = $this->buildWherePlaceholders(array_keys($aWhere), ' AND ');
        $sQuery      = "DELETE FROM `$sEntityName` WHERE $sQueryWhere";
        return [$sQuery, $aWhere];
    }

    public function buildInsertQuery($sEntityName, array $aParameters)
    {
        $sQueryColumnNames = '`' . implode('`, `', array_keys($aParameters)) . '`';
        $sPlaceholders     = ':' . implode(', :', array_keys($aParameters));
        $sQuery            = "INSERT INTO `$sEntityName` ($sQueryColumnNames) VALUES ($sPlaceholders)";
        return [$sQuery, $aParameters];
    }

    public function buildUpdateQuery($sEntityName, array $aWhere, array $aToSet)
    {
        $sQuerySet   = $this->buildWherePlaceholders(array_keys($aToSet), ', ');
        $sQueryWhere = $this->buildWherePlaceholders(array_keys($aWhere), ' AND ');
        $sQuery      = "UPDATE `$sEntityName` SET $sQuerySet WHERE $sQueryWhere";
        return [$sQuery, $aToSet + $aWhere];
    }

    public function set($sKey, $sValue)
    {
        $this->aMap[$sKey] = $sValue;
        return $this;
    }

    public function get($sKey)
    {
        if (isset($this->aMap[$sKey])) {
            return $this->aMap[$sKey];
        } else {
            throw new \BadMethodCallException("Key '$sKey' not previously set!");
        }
    }

    public function fetchAll(array $aWhere, $sOrderBy = '', $iLimit = 0, $iOffset = 0)
    {
        $this->formatToDb($aWhere);
        list($sQuery, $aParameters) = $this->buildSelectQuery($this->get('entity'), $aWhere, $sOrderBy, $iLimit, $iOffset);
        return $this->fetchAllQuery($sQuery, $aParameters);
    }

    public function fetchOne(array $aWhere, $sOrderBy = '')
    {
        $aAllRecords = $this->fetchAll($aWhere, $sOrderBy, 1);
        return isset($aAllRecords[0]) ? $aAllRecords[0] : [];
    }

    public function fetchAllQuery($sQuery, array $aParameters = [])
    {
        try {
            $aAllRecords = $this->oPdo->fetchAll($sQuery, $aParameters) ?: [];
        } catch (\PDOException $oException) {
            $sErrMsg = $oException->getMessage()
                . "\n  Entity: " . $this->get('entity')
                . "\n  Query: $sQuery"
                . "\n  Parameters: " . print_r($aParameters, true);
            throw new \RuntimeException($sErrMsg);
        }

        // Format:
        foreach ($aAllRecords as $iIndex => $aRecord) {
            $this->formatFromDb($aAllRecords[$iIndex]);
        }

        return $aAllRecords;
    }

    public function fetchOneQuery($sQuery, array $aParameters = [])
    {
        $aAllRecords = $this->fetchAllQuery($sQuery, $aParameters);
        return isset($aAllRecords[0]) ? $aAllRecords[0] : [];
    }

    public function delete(array $aWhere)
    {
        $this->formatToDb($aWhere);
        list($sQuery, $aParameters) = $this->buildDeleteQuery($this->get('entity'), $aWhere);
        try {
            $oPdoStmt = $this->oPdo->perform($sQuery, $aParameters) ?: [];
        } catch (\PDOException $oException) {
            $sErrMsg = $oException->getMessage()
                . "\n  Entity: " . $this->get('entity')
                . "\n  Query: $sQuery"
                . "\n  Parameters: " . print_r($aParameters, true);
            throw new \RuntimeException($sErrMsg);
        }
        return $oPdoStmt;
    }

    public function insert(array $aParameters)
    {
        $this->formatToDb($aParameters);
        list($sQuery, $aParameters) = $this->buildInsertQuery($this->get('entity'), $aParameters);

        try {
            $this->oPdo->perform($sQuery, $aParameters);
        } catch (\PDOException $oException) {
            $sErrMsg = $oException->getMessage() . "\n  Entity: "
                . $this->get('entity')
                . "\n  Query: $sQuery"
                . "\n  Parameters: " . print_r($aParameters, true);
            throw new \RuntimeException($sErrMsg);
        }

        return $this->oPdo->lastInsertId();
    }

    public function update(array $aWhere, array $aToSet)
    {
        $this->formatToDb($aWhere);
        $this->formatToDb($aToSet);
        list($sQuery, $aParameters) = $this->buildUpdateQuery($this->get('entity'), $aWhere, $aToSet);

        try {
            $oPdoStmt = $this->oPdo->perform($sQuery, $aParameters);
        } catch (\PDOException $oException) {
            $sErrMsg = $oException->getMessage()
                . "\n  Entity: " . $this->get('entity')
                . "\n  Query: $sQuery"
                . "\n  Parameters: " . print_r($aParameters, true);
            throw new \RuntimeException($sErrMsg);
        }

        return $oPdoStmt;
    }

    /**
     * @return ExtendedPdoInterface
     */
    protected function getPdo()
    {
        $oPdo = $this->oPdo;
        $oPdo->connect();
        return $oPdo;
    }

    public function disconnectPdo()
    {
        /** @var $oPdo ExtendedPdo */
        $oPdo = $this->oPdo;
        $oPdo->disconnect();
    }
}
