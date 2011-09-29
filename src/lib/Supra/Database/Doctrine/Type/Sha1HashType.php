<?php

namespace Supra\Database\Doctrine\Type;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Column type to store sha1 hashes
 */
class Sha1HashType extends Type
{
	const NAME = 'sha1';
	
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
		$fieldDeclaration['length'] = 40;
		$fieldDeclaration['fixed'] = true;
		
		$sql = $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
		
		return $sql;
	}
}
