<?php

namespace Supra\Log\Writer;

use Supra\Log\LogEvent;
use Supra\Log\Exception\LogException;

/**
 * Writes to several log writers simultaneously
 */
class ChainWriter extends WriterAbstraction
{
	/**
	 * Array of log writers
	 * @var array
	 */
	protected $writers = array();

	/**
	 * @param WriterAbstraction $writer
	 */
	public function addWriter(WriterAbstraction $writer)
	{
		$this->writers[] = $writer;
	}
	
	/**
	 * {@inheritdoc}
	 * @param LogEvent $event 
	 */
	public function write(LogEvent $event)
	{
		$failure = null;
		
		/* @var $writer WriterAbstraction */
		foreach ($this->writers as $writer) {
			
			// Catch exceptions so all writers are executed
			try {
				$writer->write($event);
			} catch (LogException $failure) {
				
			}
		}
		
		// Throw the last exception raised
		if ($failure instanceof \Exception) {
			throw $failure;
		}
	}
	
	/**
	 * Not used method
	 * @param LogEvent $event
	 */
	protected function _write(LogEvent $event)
	{
		
	}
}
