<?php
/*
 *
 * Author of this code is Geoffroy Aubry => https://github.com/geoffroy-aubry
 *
 */

namespace Asticode\DataMapper\Mapper;

use Aura\Sql\ExtendedPdoInterface;

/**
 * Add common SQL transaction methods.
 */
trait TransactionTrait
{
    /**
     * @return ExtendedPdoInterface
     */
    abstract protected function getPdo();

    /**
     *
     * Begins a transaction and turns off autocommit mode.
     *
     * @return bool True on success, false on failure.
     * @see http://php.net/manual/en/pdo.begintransaction.php
     *
     */
    public function beginTransaction()
    {
        /* @var $this->oPdo ExtendedPdoInterface */
        return $this->getPdo()->beginTransaction();
    }

    /**
     *
     * Commits the existing transaction and restores autocommit mode.
     *
     * @return bool True on success, false on failure.
     * @see http://php.net/manual/en/pdo.commit.php
     *
     */
    public function commit()
    {
        return $this->getPdo()->commit();
    }

    /**
     *
     * Rolls back the current transaction, and restores autocommit mode.
     *
     * @return bool True on success, false on failure.
     * @see http://php.net/manual/en/pdo.rollback.php
     *
     */
    public function rollBack()
    {
        return $this->getPdo()->commit();
    }
}
