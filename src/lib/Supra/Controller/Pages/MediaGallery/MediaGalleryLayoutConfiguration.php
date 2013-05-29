<?php

namespace Supra\Controller\Pages\MediaGallery;

use Supra\Controller\Pages\Exception\ConfigurationException;

class MediaGalleryLayoutConfiguration implements \Supra\Configuration\ConfigurationInterface
{
	/**
	 * Name/ID
	 * @var string
	 */
	public $name;
	
	/**
	 * @var string
	 */
	public $title;
	
	/**
	 * Layout file name
	 * @var string
	 */
	public $file;
	
	/**
	 * @var string
	 */
	private $fileContent;
	
	/**
	 * @return string
	 */
	public function getLayoutFileContent()
	{
		if ($this->fileContent === null) {
			if ( ! file_exists($this->file)) {
				throw new ConfigurationException("Layout file by path {$this->file} is not accessible");
			}
			
			$this->fileContent = file_get_contents($this->file);
		}

		return $this->fileContent;
	}
	
	/**
	 */
	public function configure()
	{
		if (empty($this->name)) {
			throw new ConfigurationException('Layout name cannot be empty');
		}
		
		if (mb_strpos($this->file, '..') !== false ) {
			throw new ConfigurationException('Invalid characters found in file property');
		}
		
		if (empty($this->title)) {
			$this->title = ucfirst($this->name);
		}
	}
}