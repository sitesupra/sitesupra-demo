<?php

namespace Supra\Cms\MediaLibrary\Medialibrary;

use Supra\FileStorage\ImageProcessor;
use Supra\FileStorage\Exception;
use Supra\FileStorage\Entity;
use Supra\Cms\MediaLibrary\MediaLibraryAbstractAction;
use Supra\Cms\Exception\CmsException;
use Supra\FileStorage\Entity\Folder;
use Supra\Cms\MediaLibrary\ApplicationConfiguration;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\Configuration\PropertyConfiguration;

class MedialibraryAction extends MediaLibraryAbstractAction
{
	// types for MediaLibrary UI


	
	const DUPLICATE_NAME_PATTERN = '%s (%d).%s';

	//















	/**
	 * Helper method to fetch config value from ApplicationConfig class
	 * for media library
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	private function getApplicationConfigValue($key, $default = null)
	{
		$appConfig = ObjectRepository::getApplicationConfiguration($this);

		if ($appConfig instanceof ApplicationConfiguration) {
			if (property_exists($appConfig, $key)) {
				return $appConfig->$key;
			}
		}

		if ( ! is_null($default)) {
			return $default;
		}

		return null;
	}



}