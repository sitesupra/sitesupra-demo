<?php

namespace Supra\Controller\Pages\Exception;

/**
 * Thrown when some bound resource is missing when page/localization restore is done
 */
class MissingResourceOnRestore extends RuntimeException
{
	/**
	 * @var string
	 */
	private $className;

	/**
	 * @var string
	 */
	private $id;
	
	/**
	 * @param string $className
	 * @param string $id
	 */
	public function __construct($className, $id)
	{
		$this->className = $className;
		$this->id = $id;
	}

	/**
	 * @return string
	 */
	public function getClassName()
	{
		return $this->className;
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * @return string
	 */
	public function getMissingResourceName()
	{
		$name = $this->className;
		
		// Let's leave the basename only
		$name = preg_replace('/^.*\\\\/', '', $name);
		
		$name .= ' (#' . $this->id . ')';
		
		return $name;
	}
}
