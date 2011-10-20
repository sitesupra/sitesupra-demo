<?php

namespace Supra\Database\Doctrine\Type;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;


class BlockType extends StringType
{
	const NAME = 'block';
	
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
		$fieldDeclaration['length'] = 40;
		$fieldDeclaration['fixed'] = true;
		
		$sql = $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
		
		return $sql;
	}
	
	/**
	 * {@inheritdoc}
	 * @param Path $value
	 * @param AbstractPlatform $platform
	 * @return string
	 */
	public function convertToDatabaseValue($value, AbstractPlatform $platform)
	{
		if (is_null($value) || ! ($value instanceof \Supra\Controller\Pages\Entity\Abstraction\Block)) {
			return null;
		}

		$id = $value->getId();

		return $id;
	}

	/**
	 * {@inheritdoc}
	 * @param string $value
	 * @param AbstractPlatform $platform
	 * @return Template
	 */
	public function convertToPHPValue($value, AbstractPlatform $platform)
	{
		if (is_null($value)) {
			return null;
		}
		
		return $value;
	}
	
}
