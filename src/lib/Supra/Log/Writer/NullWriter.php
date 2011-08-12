<?php

namespace Supra\Log\Writer;

use Supra\Log\LogEvent;

/**
 * Null log writer
 */
class NullWriter extends WriterAbstraction
{
	/**
	 * Ignore the event
	 * @param LogEvent $event
	 */
	protected function _write(LogEvent $event)
	{}
}