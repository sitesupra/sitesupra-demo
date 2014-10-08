<?php

namespace Supra\Core\Event;


class ConsoleEvent extends \Symfony\Component\Console\Event\ConsoleEvent
{
	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @param array $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}


}