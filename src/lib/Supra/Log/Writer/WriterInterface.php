<?php

namespace Supra\Log\Writer;

use Supra\Log\Formatter;
use Supra\Log\Filter;
use Supra\Log\LogEvent;

/**
 * Log writer interface
 */
interface WriterInterface
{
	/**
	 * Log writer constructor
	 * @param array $parameters
	 */
	function __construct(array $parameters = array());

	/**
	 * Setting logger name
	 * @param string $name
	 */
	public function setName($name);

	/**
	 * Get the plugin configuration
	 * @return array
	 */
	public function getConfiguration();

	/**
	 * Log event write method
	 * @param LogEvent $event
	 */
	public function write(LogEvent $event);

	/**
	 * Set log formatter
	 * @param Formatter\FormatterInterface $formatter
	 */
	public function setFormatter(Formatter\FormatterInterface $formatter);

	/**
	 * Get log formatter
	 * @return Formatter\FormatterInterface
	 */
	public function getFormatter();

	/**
	 * Set log filter
	 * @param Filter\FilterInterface $filter
	 * @param boolean $append
	 */
	public function addFilter(Filter\FilterInterface $filter, $append = true);

}