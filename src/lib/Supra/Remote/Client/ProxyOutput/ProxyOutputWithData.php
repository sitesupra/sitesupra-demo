<?php

namespace Supra\Remote\Client\ProxyOutput;

use Supra\Console\Output\CommandOutputWithData;

class ProxyOutputWithData extends ProxyOutput implements CommandOutputWithData
{

	/**
	 * @var mixed
	 */
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
