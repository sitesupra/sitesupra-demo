<?php

namespace Supra\Core\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Column type to store supra ID hashes
 */
class SupraIdType extends Type
{
	const CN = __CLASS__;
	
	const NAME = 'supraId20';
	
	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getName()
	{
		return self::NAME;
	}

	/**
	 * {@inheritdoc}
	 * @param array $fieldDeclaration
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
	{
		$fieldDeclaration['length'] = 20;
		$fieldDeclaration['fixed'] = true;
		
		$sql = $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
		
		return $sql;
	}
}
