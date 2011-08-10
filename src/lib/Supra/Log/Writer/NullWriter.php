<?php

namespace Supra\Log\Writer;

/**
 * Null log writer
 */
class NullWriter extends WriterAbstraction
{
	/**
	 * Ignore the event
	 * @param array $event
	 */
	protected function _write($event)
	{}
}