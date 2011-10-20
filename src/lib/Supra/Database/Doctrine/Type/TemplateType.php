<?php

namespace Supra\Database\Doctrine\Type;

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Platforms\AbstractPlatform;


class TemplateType extends StringType
{
	const NAME = 'template';
	
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
		if (is_null($value) || ! ($value instanceof \Supra\Controller\Pages\Entity\Abstraction\AbstractPage)) {
			return null;
		}

		$tplId = $value->getId();

		return $tplId;
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

		$draftEm = \Supra\ObjectRepository\ObjectRepository::getEntityManager('Supra\Cms');
		$tpl = $draftEm->find(\Supra\Controller\Pages\Request\PageRequest::TEMPLATE_ENTITY, $value);
		
		return $tpl;
	}
	
}
