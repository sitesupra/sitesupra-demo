<?php

namespace Supra\Remote\Client\ProxyOutput;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\StreamOutput;

class ProxyOutput extends Output
{
	/**
	 * @var array
	 */
	protected $buffer;

	/**
	 * @param StreamOutput $output 
	 */
	function __construct(Output $output)
	{
		$this->buffer = array();

		parent::__construct($output->getVerbosity(), $output->isDecorated(), $output->getFormatter());
	}

	/**
	 * @param type $message
	 * @param type $newline 
	 */
	public function doWrite($message, $newline)
	{
		$this->buffer[] = array($message, $newline);
	}

	/**
	 * This is to be called from RemoteCommandService ONLY!
	 */
	public function unproxy(Output $output)
	{
		foreach ($this->buffer as $bufferItem) {

			$message = $bufferItem[0];
			$newline = $bufferItem[1];

			$output->doWrite($message, $newline);
		}
	}

}

