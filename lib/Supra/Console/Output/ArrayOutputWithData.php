<?php

namespace Supra\Console\Output;

class ArrayOutputWithData extends ArrayOutput implements CommandOutputWithData
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
