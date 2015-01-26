<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

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

	public function requiresSQLCommentHint(AbstractPlatform $platform)
	{
		return true;
	}
}
