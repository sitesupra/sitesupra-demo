<?php

namespace Supra\Log\Writer;

/**
 * Mock log writer
 */
class MockWriter extends WriterAbstraction
{
	/**
	 * List of written logs
	 * @var array
	 */
	protected $events = array();

	/**
	 * Store the event inside the property
	 * @param array $event
	 */
	protected function _write($event)
	{
		$this->events[] = $event;
	}

	/**
	 * Get written events
	 * @return array
	 */
	public function getEvents()
	{
		return $this->events;
	}
}