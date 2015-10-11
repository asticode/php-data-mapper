<?php
namespace Asticode\DataMapper\Repository;

use Asticode\DataMapper\Mapper\AbstractMapper;

abstract class AbstractRepository
{
    // Attributes
    protected $oMapper;

    // Construct
    public function __construct(AbstractMapper $oMapper)
    {
        // Initialize
        $this->oMapper = $oMapper;
    }

    public function disconnectPdo()
    {
        $this->oMapper->disconnectPdo();
    }
}