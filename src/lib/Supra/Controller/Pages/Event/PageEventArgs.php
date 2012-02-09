<?php

namespace Supra\Controller\Pages\Event;

use Doctrine\Common\EventArgs;

class PageEventArgs extends EventArgs
{
	/**
	 * @var array
	 */
	private $properties = array();
	
	
	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getProperty($name) 
	{
		if (isset($this->properties[$name])) {
			return $this->properties[$name];
		}
		
		return null;
	}
	
	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function setProperty($name, $value)
	{
		$this->properties[$name] = $value;
	}
}
