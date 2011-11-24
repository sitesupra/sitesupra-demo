<?php

namespace Supra\Database\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;

/**
 * Type that indicates field with this name is not recognized.
 */
class UnknownType extends Type
{
	private $name = 'unknown';
	
    public function getName()
    {
        return $this->name;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        throw DBALException::unknownColumnType($this->getName());
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        throw DBALException::unknownColumnType($this->getName());
    }

    public function getBindingType()
    {
        throw DBALException::unknownColumnType($this->getName());
    }
}