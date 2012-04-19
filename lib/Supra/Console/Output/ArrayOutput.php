<?php

namespace Supra\Console\Output;

use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

/**
 * Output storing data in array
 */
class ArrayOutput extends Output
{
	/**
	 * Output lines
	 * @var array
	 */
	private $output = array();
	
	/**
	 * Current line
	 * @var string
	 */
	private $lastLine;
	
	/**
	 * @param int $verbosity
	 * @param boolean $decorated
	 * @param OutputFormatterInterface $formatter
	 */
	public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = null, OutputFormatterInterface $formatter = null)
	{
		$decorated = false;
		parent::__construct($verbosity, $decorated, $formatter);
		
		$this->newLine();
	}
	
	/**
	 * Starts new line
	 */
	private function newLine()
	{
		unset($this->lastLine);
		$this->lastLine = '';
		$this->output[] = &$this->lastLine;
	}
	
	/**
	 * {@inheritdoc}
	 * @param string $message
	 * @param boolean $newline
	 */
	public function doWrite($message, $newline)
	{
		$this->lastLine .= $message;
		
		if ($newline) {
			$this->newLine();
		}
	}
	
	/**
	 * @return array
	 */
	public function getOutput()
	{
		return $this->output;
	}
}
