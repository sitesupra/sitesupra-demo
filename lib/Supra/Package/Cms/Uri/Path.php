<?php

namespace Supra\Package\Cms\Uri;

/**
 * URI path object
 */
class Path
{
	/**
	 * Limiting the path length to the IE limit
	 * TODO: implement the check internally
	 */
	const MAX_LENGTH = 2083;
	
	/**
	 * Specifies the output format
	 */
	const FORMAT_NO_DELIMITERS = 0;
	const FORMAT_LEFT_DELIMITER = 1;
	const FORMAT_RIGHT_DELIMITER = 2;
	const FORMAT_BOTH_DELIMITERS = 3;
	
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
	public function __construct($path = '', $separator = '/')
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
		$pathList = array();
		
		if ($path !== '') {
			$pathList = explode($this->separator, $path);
		}
		
		$this->setPathList($pathList);
	}

	/**
	 * @param integer $format
	 * @return string
	 */
	public function getPath($format = self::FORMAT_NO_DELIMITERS)
	{
		$string = implode($this->separator, $this->path);
		$string = self::format($string, $format, $this->separator);
		
		return $string;
	}
	
	/**
	 * Formats the path string according to the mode
	 * @param string $string
	 * @param integer $format
	 */
	public static function format($string, $format, $separator = '/')
	{
		// Trim
		$string = trim($string, $separator);
		
		// Empty path case
		if ($string === '') {
			if ($format == self::FORMAT_BOTH_DELIMITERS) {
				return $separator;
			} else {
				return $string;
			}
		}
		
		// Add delimiters
		if ($format & self::FORMAT_LEFT_DELIMITER) {
			$string = $separator . $string;
		}
		if ($format & self::FORMAT_RIGHT_DELIMITER) {
			$string = $string . $separator;
		}
		
		return $string;
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
		// Remove empty elements
		foreach ($pathList as $pathIndex => $pathValue) {
			if ($pathValue === '') {
				unset($pathList[$pathIndex]);
			}
		}
		
		$this->path = $pathList;
		$this->setDepth(count($this->path));
	}

	/**
	 * @param integer $format
	 * @return string
	 */
	public function getFullPath($format = self::FORMAT_NO_DELIMITERS)
	{
		$parts = array();
		foreach ($this->basePathParts as $path) {
			$part = $path->getFullPath();
			
			if ($part != '') {
				$parts[] = $part;
			}
		}
		$part = $this->getPath();
		if ($part != '') {
			$parts[] = $part;
		}
		$string = implode($this->separator, $parts);
		$string = self::format($string, $format, $this->separator);
		
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
			$result = (strcmp($probe, $pathString) == 0);
		} else {
			$result = (strcasecmp($probe, $pathString) == 0);
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
		
		$pathList = array_slice($this->path, $pathDepth);
		$this->setPathList($pathList);
	}
	
	/**
	 * Get request base path with arbitrary offset
	 * @param integer $offset
	 * @param integer $format
	 * @return string
	 */
	public function getBasePath($offset = 0, $format = self::FORMAT_NO_DELIMITERS)
	{
		$basePathList = $this->basePathParts;
		
		if ($offset > 0) {
			$basePathList = array_slice($basePathList, 0, -$offset);
		}
		
		$parts = new Path('', $this->separator);
		
		foreach ($basePathList as $path) {
			$parts->append($path);
		}
		
		$basePathString = $parts->getFullPath($format);
		
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
	
	/**
	 * @param Path $path
	 * @return boolean
	 */
	public function equals(Path $path = null)
	{
		if (is_null($path)) {
			return false;
		}
		
		// Same object (maybe NullPath)
		if ($this === $path) {
			return true;
		}
		
		$thisString = $this->getFullPath();
		$pathString = $path->getFullPath();
		$result = null;
		
		// Does not equal if any of path is NULL
		if (is_null($thisString) || is_null($pathString)) {
			return false;
		}
		
		if ($this->caseSensitive) {
			$result = (strcmp($thisString, $pathString) === 0);
		} else {
			$result = (strcasecmp($thisString, $pathString) === 0);
		}
		
		return $result;
	}
	
	/**
	 * @param Path $path
	 * @param Path $withPath
	 */
	public static function compare(Path $path = null, Path $withPath = null)
	{
		// Doesn't equal by default
		$equals = false;
		
		if ( ! is_null($path)) {
			// Send to equals() function if $path not null
			$equals = $path->equals($withPath);
		} else {
			// Equals if both are null
			if (is_null($withPath)) {
				$equals = true;
			}
		}
		
		return $equals;
	}
	
	/**
	 * @param Path $path
	 * @return Path
	 */
	public function append(Path $path = null)
	{
		if ( ! is_null($path)) {
			$pathList = array_merge($this->path, $path->basePathParts, $path->getPathList());
			$this->setPathList($pathList);
		}
		
		return $this;
	}
	
	/**
	 * @param string $pathString
	 * @return Path
	 */
	public function appendString($pathString)
	{
		$path = new Path($pathString);
		$this->append($path);
		
		return $this;
	}
	
	/**
	 * @param Path $path
	 * @return Path
	 */
	public function prepend(Path $path = null)
	{
		if ( ! is_null($path)) {
			$pathList = array_merge($path->basePathParts, $path->getPathList(), $this->path);
			$this->setPathList($pathList);
		}
		
		return $this;
	}
	
	/**
	 * @param string $pathString
	 * @return Path
	 */
	public function prependString($pathString)
	{
		$path = new Path($pathString);
		$this->prepend($path);
		
		return $this;
	}
	
	/**
	 * @return boolean
	 */
	public function isEmpty()
	{
		$pathString = $this->getFullPath();
		$empty = ($pathString === '');
		
		return $empty;
	}
}
