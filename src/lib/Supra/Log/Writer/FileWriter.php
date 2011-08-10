<?php

namespace Supra\Log\Writer;

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
		'file' => 'supra.log',
	);
	
	/**
	 * Log writer constructor
	 * @param array $parameters
	 */
	function __construct(array $parameters = array())
	{
		// parent constructor
		parent::__construct($parameters);
		
		// build full url
		$this->parameters['url'] = rtrim($this->parameters['folder'], '/\\') . DIRECTORY_SEPARATOR . ltrim($this->parameters['file'], '/\\');
	}
	
}