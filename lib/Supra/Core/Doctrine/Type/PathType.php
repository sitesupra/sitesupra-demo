<?php

namespace Supra\Core\Doctrine\Type;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Supra\Package\Cms\Uri\Path;

/**
 * Doctrine field type storing path
 */
class PathType extends StringType
{
	const CN = __CLASS__;
	
	const NAME = 'path';
	
	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return self::NAME;
	}

	/**
	 * {@inheritdoc}
	 * @param array $fieldDeclaration
	 * @param AbstractPlatform $platform
	 * @return 
	 */
	public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
	{
		if (empty($fieldDeclaration['length'])) {
			$fieldDeclaration['length'] = Path::MAX_LENGTH;
		}
		$type = $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
		
		return $type;
	}
	
	/**
	 * {@inheritdoc}
	 * @param Path $value
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		if (is_null($value)) {
			return null;
		}
		
		$string = $value->getFullPath();
		
		return $string;
	}

	/**
	 * {@inheritdoc}
	 * @param string $value
	 * @param AbstractPlatform $platform
	 * @return Path
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if (is_null($value)) {
			return null;
		}
		
		$path = new Path($value);
		
		return $path;
	}
	
}
