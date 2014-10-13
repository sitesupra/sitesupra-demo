<?php

namespace Supra\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class DataAgnosticEvent extends Event
{
	protected $data;

	/**
	 * @return mixed
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * @param mixed $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}
}
