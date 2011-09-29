<?php

namespace Supra\Uri;

/**
 * URI path object
 */
class Path
{
	/**
	 * @var string
	 */
	protected $separator = '/';

	/**
	 * @var Path[]
	 */
	protected $basePathParts = array();

	/**
	 * @var string[]
	 */
	protected $path;

	/**
	 * @var int
	 */
	protected $depth;

	/**
	 * @var boolean
	 */
	protected $caseSensitive = false;

	/**
	 * @param string $path
	 * @param string $separator
	 */
	public function __construct($path, $separator = '/')
	{
		$this->setSeparator($separator);
		$this->setPath($path);
	}

	/**
	 * @param string $separator
	 */
	public function setSeparator($separator = '/')
	{
		$this->separator = $separator;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		$path = trim($path, $this->separator);
		if ($path == '') {
			$this->path = array();
		} else {
			$this->path = explode($this->separator, $path);
		}
		$this->setDepth(\count($this->path));
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return implode($this->separator, $this->path);
	}

	/**
	 * @return string[]
	 */
	public function getPathList()
	{
		return $this->path;
	}
	
	/**
	 * @param array $pathList
	 */
	public function setPathList(array $pathList)
	{
		$this->path = $pathList;
	}

	/**
	 * 
	 */
	public function getFullPath()
	{
		$parts = array();
		foreach ($this->basePathParts as $path) {
			$part = $path->getFullPath();
			$part = trim($part, $this->separator);
			if ($part != '') {
				$parts[] = $part;
			}
		}
		$part = $this->getPath();
		if ($part != '') {
			$parts[] = $part;
		}
		$string = implode($this->separator, $parts);
		if ($string == '') {
			return $this->separator;
		} else {
			$string = $this->separator . $string;
		}
		return $string;
	}

	/**
	 * @return int
	 */
	public function getDepth()
	{
		return $this->depth;
	}

	/**
	 * @param int $depth
	 */
	protected function setDepth($depth)
	{
		$this->depth = $depth;
	}

	/**
	 * @param boolean $caseSensitive
	 */
	public function setCaseSensitive($caseSensitive = true)
	{
		$this->caseSensitive = $caseSensitive;
	}

	/**
	 * @return string
	 */
	public function getSeparator()
	{
		return $this->separator;
	}

	/**
	 * @param Path $path
	 */
	public function startsWith(Path $path)
	{
		$probe = null;
		$result = null;

		if ($this->separator != $path->getSeparator()) {
			throw new \Exception("The path separators for {$this} and {$path} must be equal for both parts in method " . __METHOD__);
		}

		$pathString = $path->getPath();
		$pathDepth = $path->getDepth();

		if ($pathDepth == 0) {
			return true;
		} elseif ($pathDepth > $this->depth) {
			return false;
		} elseif ($pathDepth == $this->depth) {
			$probe = $this->getPath();
		} else {
			$pathString .= $this->separator;
			$length = strlen($pathString);
			$probe = substr($this->getPath(), 0, $length);
		}
		
		if ($this->caseSensitive) {
			$result = (\strcmp($probe, $pathString) == 0);
		} else {
			$result = (\strcasecmp($probe, $pathString) == 0);
		}

		return $result;
	}

	/**
	 * @param Path $path
	 */
	public function setBasePath(Path $path)
	{
		// check if the path starts with the base path
		if ( ! $this->startsWith($path)) {
			throw new \Exception("Cannot set path {$path} as base path of {$this}, does not start with");
		}

		// Set base path
		$this->basePathParts[] = $path;

		// Remove the base path part
		$pathDepth = $path->getDepth();
		$this->path = \array_slice($this->path, $pathDepth);
		$this->setDepth(\count($this->path));
	}
	
	/**
	 * Get request base path with arbitrary offset
	 * @param integer $offset
	 * @return string
	 */
	public function getBasePath($offset = 0)
	{
		$basePathList = $this->basePathParts;
		
		if ($offset > 0) {
			$basePathList = array_slice($basePathList, 0, -$offset);
		}
		
		$parts = array();
		
		foreach ($basePathList as $path) {
			$part = $path->__toString();
			$part = trim($part, $this->separator);
			if ($part != '') {
				$parts[] = $part;
			}
		}
		
		$basePathString = implode($this->separator, $parts);
		
		return $basePathString;
	}
	
	/**
	 * Helper function to concat 2 string paths
	 * TODO: change everything with Path objects
	 * @param string $path
	 * @param string $withPath
	 * @param string $separator
	 * @return string
	 */
	public static function concat($path, $withPath, $separator = '/')
	{
		$path = rtrim($path, $separator);
		$withPath = ltrim($withPath, $separator);
		$fullPath = null;
		
		if ($path == '' || $withPath == '') {
			$fullPath = $path . $withPath;
		} else {
			$fullPath = $path . $separator . $withPath;
		}
		
		return $fullPath;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		$parts = array();
		foreach ($this->basePathParts as $path) {
			$part = $path->__toString();
			$part = trim($part, $this->separator);
			if ($part != '') {
				$parts[] = '[' . $part . ']';
			}
		}
		$parts[] = $this->getPath();
		$string = implode($this->separator, $parts);
		if ($string == '') {
			return $this->separator;
		} else {
			$string = $this->separator . $string;
		}
		return $string;
	}
}
