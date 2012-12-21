<?php

namespace Supra\Log\Writer;

use Supra\Log\LogEvent;

/**
 * Stream log writer
 */
class FileWriter extends StreamWriter
{
	/**
	 * Default configuration
	 * @var array
	 */
	public static $defaultParameters = array(
		'folder' => \SUPRA_LOG_PATH,
		'file' => null,
		'fileBase' => 'supra',
		'fileExtension' => '.log',
	);
	
	/**
	 * Log writer constructor
	 * @param array $parameters
	 */
	public function __construct(array $parameters = array())
	{
		// parent constructor
		parent::__construct($parameters);
		
		$fileName = $this->getFileName();
		
		// build full url
		$url = rtrim($this->parameters['folder'], '/\\')
				. DIRECTORY_SEPARATOR
				// minor sanitizing
				. str_replace(DIRECTORY_SEPARATOR, '', $fileName);

		$this->parameters['url'] = $url;
	}

	/**
	 * Overriden so we can chmod the file
	 * @param LogEvent $event
	 * @return resource
	 */
	protected function getStream(LogEvent $event)
	{
		$filename = $this->formatUrl($this->parameters['url']);

		if ( ! file_exists($filename)) {
			touch($filename);
			chmod($filename, SITESUPRA_FILE_PERMISSION_MODE);
		}

		return parent::getStream($event);
	}
	
	/**
	 * @return string 
	 */
	protected function getFileName()
	{
		$fileName = $this->parameters['file'];
		
		if (empty($fileName)) {
			$fileName = $this->parameters['fileBase']
					. $this->parameters['fileExtension'];
		}
		
		return $fileName;
	}
}
