<?php

namespace Supra\Package\Cms\Uri;

/**
 * Null path with no path parts
 */
class NullPath extends Path
{
	/**
	 * @var NullPath
	 */
	private static $instance = null;

	/**
	 * @return NullPath
	 */
	public static function getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	public function __toString()
	{
		return '';
	}

	public function append(Path $path = null)
	{
		return $this;
	}

	public function appendString($pathString)
	{
		return $this;
	}

	public function equals(Path $path = null)
	{
		return false;
	}

	public function getBasePath($offset = 0, $format = self::FORMAT_NO_DELIMITERS)
	{
		return null;
	}

	public function getDepth()
	{
		return null;
	}

	public function getFullPath($format = self::FORMAT_NO_DELIMITERS)
	{
		return null;
	}

	public function getPath($format = self::FORMAT_NO_DELIMITERS)
	{
		return null;
	}

	public function getPathList()
	{
		return array();
	}

	public function getSeparator()
	{
		return null;
	}

	public function isEmpty()
	{
		return true;
	}

	public function prepend(Path $path = null)
	{
		return $this;
	}

	public function prependString($pathString)
	{
		return $this;
	}

	public function startsWith(Path $path)
	{
		return false;
	}

}
