<?php

namespace Supra\Remote\Client\ProxyOutput;

use Supra\Console\Output\CommandOutputWithData;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\StreamOutput;

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

	public function unproxy(Output $output)
	{
		parent::unproxy($output);
		
		$output->setData($this->getData());
	}

}
