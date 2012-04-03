<?php

namespace Supra\Configuration\Writer;

use Supra\Configuration\Parser\AbstractParser;

abstract class AbstractWriter
{

	/**
	 * @var string
	 */
	protected $filename;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @param string $filename
	 * @param string $data 
	 */
	function writeData($filename, $data)
	{
		$this->filename = $filename;
		$this->data = $data;

		$this->write();
	}

	/**
	 * @param $data array 
	 */
	abstract protected function write();
	
	abstract function setParser(AbstractParser $parser);
}

