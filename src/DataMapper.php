<?php
namespace Asticode\DataMapper;

use Asticode\DataMapper\Mapper\MapperFactory;
use Aura\Sql\ConnectionLocatorInterface;

class DataMapper
{
    // Attributes
    private $sNamespace;
    private $oMapperFactory;
    private $oRepositoryFactory;

    // Construct
    public function __construct(ConnectionLocatorInterface $oDbConnectionLocator, $sNamespace)
    {
        // Initialize
        $this->sNamespace = $sNamespace;
        $this->oMapperFactory = new MapperFactory($oDbConnectionLocator, $sNamespace);
        $this->oRepositoryFactory = new RepositoryFactory($this->oMapperFactory, $sNamespace);
    }

    public function getRepository($sRepositoryName, $sNamespace = '')
    {
        return $this->oRepositoryFactory->getRepository($sRepositoryName, $sNamespace);
    }
}