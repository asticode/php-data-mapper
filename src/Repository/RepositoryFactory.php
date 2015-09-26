<?php
namespace Asticode\DataMapper\Repository;

use Asticode\DataMapper\Mapper\MapperFactory;
use Asticode\Toolbox\ExtendedString;

class RepositoryFactory
{
    // Attributes
    private $aRepositories;
    private $oMapperFactory;
    private $sNamespace;

    // Construct
    public function __construct(
        MapperFactory $oMapperFactory,
        $sNamespace
    ) {
        // Initialize
        $this->aRepositories = [];
        $this->oMapperFactory = $oMapperFactory;
        $this->sNamespace = $sNamespace;
    }

    public function getRepository($sRepositoryName, $sNamespace)
    {
        if (empty($this->aRepositories[$sRepositoryName])) {
            // Get class name
            $sClassName = sprintf(
                '\\%1$s\\Repository\\%2$s',
                $sNamespace === '' ? $this->sNamespace : $sNamespace,
                ExtendedString::toCamelCase($sRepositoryName, '_', true)
            );

            // Create repository
            $this->aRepositories[$sRepositoryName] = new $sClassName(
                $this->oMapperFactory->getMapper($sRepositoryName)
            );
        }
        return $this->aRepositories[$sRepositoryName];
    }
}