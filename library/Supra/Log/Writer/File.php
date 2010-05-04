<?php

namespace Supra\Log\Writer;

use Supra\Log\Logger;

/**
 * Stream log writer
 */
class File extends Stream
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