<?php

namespace Supra\FileStorage\Configuration;

use Supra\Configuration\ConfigurationInterface;
use Supra\Loader\Loader;
use Supra\ObjectRepository\ObjectRepository;
use Supra\FileStorage\FileStorage;
use Supra\FileStorage\Validation\ExtensionUploadFilter;

class FileStorageConfiguration implements ConfigurationInterface
{
	/**
	 * @var string
	 */
	public $internalPath = 'files';

	/**
	 * @var string
	 */
	public $externalPath = 'files';

	/**
	 * @var array
	 */
	public $fileExtensions = array('php', 'phtml', 'php3', 'php4', 'js', 'shtml',
			'pl' ,'py', 'cgi', 'sh', 'asp', 'exe', 'bat', 'jar', 'phar'
	);

	/**
	 * @var int
	 */
	public $fileExtensionFilterMode = ExtensionUploadFilter::MODE_BLACKLIST;

	/**
	 * @var array
	 */
	public $uploadFilters = array();

	/**
	 * @var array
	 */
	public $fileUploadFilters = array();

	/**
	 * @var array
	 */
	public $folderUploadFilters = array();

	/**
	 * @var array
	 */
	public $fileProperties = array();

	/**
	 * @var mixed
	 */
	public $caller;

	/**
	 * {@inheritDoc}
	 */
	public function configure()
	{
		$storage = new FileStorage();

		$storage->setInternalPath($this->internalPath);
		$storage->setExternalPath($this->externalPath);

		if ( ! empty($this->extensions)) {
			$extensionFilter = new ExtensionUploadFilter();
			$extensionFilter->setMode($this->extensionFilterMode);
			$extensionFilter->addItems($this->extensions);

			$storage->addFileUploadFilter($extensionFilter);
		}

		foreach ($this->uploadFilters as $filter) {

			if (is_string($filter)) {
				$filter = Loader::getClassInstance($filter);
			}

			$storage->addFileUploadFilter($filter);
			$storage->addFolderUploadFilter($filter);
		}

		foreach ($this->fileUploadFilters as $filter) {

			if (is_string($filter)) {
				$filter = Loader::getClassInstance($filter);
			}
			
			$storage->addFileUploadFilter($filter);
		}

		foreach ($this->folderUploadFilters as $filter) {
			
			if (is_string($filter)) {
				$filter = Loader::getClassInstance($filter);
			}
			
			$storage->addFolderUploadFilter($filter);
		}

		foreach ($this->fileProperties as $propertyConfig) {
			/* @var $propertyConfig FilePropertyConfiguration */
			$storage->addFilePropertyConfiguration($propertyConfig);
		}

		if ($this->caller !== null) {
			ObjectRepository::setFileStorage($this->caller, $storage);
		} else {
			ObjectRepository::setDefaultFileStorage($storage);
		}
	}
}