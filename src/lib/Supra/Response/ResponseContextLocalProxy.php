<?php

namespace Supra\Response;

use Supra\Response\TwigResponse;

/**
 * Keeps local context changes and proxies them to the global context
 */
class ResponseContextLocalProxy extends ResponseContext
{
	const RESOURCE_FILE_OFFSET = 'resources';
	
	/**
	 * @var ResponseContext
	 */
	private $localContext;
	
	/**
	 * Bind global context data to be used, creates separate local context for
	 * keeping local changes
	 * @param ResponseContext $mainContext
	 */
	public function __construct(ResponseContext $mainContext)
	{
		parent::__construct($mainContext);
		$this->layoutSnippetResponses = &$mainContext->layoutSnippetResponses;
		
		$this->localContext = new ResponseContext();
	}
	
	/**
	 * Serializing local context only
	 * @return string
	 */
	public function serialize()
	{
		return serialize($this->localContext);
	}
	
	/**
	 * Makes sure local context is created
	 * @param string $serialized
	 */
	public function unserialize($serialized)
	{
		$this->localContext = unserialize($serialized);
		
		if ( ! $this->localContext instanceof ResponseContext) {
			$this->localContext = new ResponseContext();
		}
	}
	
	/**
	 * Overrides ArrayIterator function
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value)
	{
		$this->localContext->offsetSet($key, $value);
		parent::offsetSet($key, $value);
	}
	
	/**
	 * Overrides ArrayIterator function
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function offsetUnset($key)
	{
		$this->localContext->offsetUnset($key);
		parent::offsetUnset($key);
	}
	
	/**
	 * @param string $key
	 * @param TwigResponse | string $value 
	 */
	public function addToLayoutSnippet($key, $snippet)
	{
		$this->localContext->addToLayoutSnippet($key, $snippet);
		parent::addToLayoutSnippet($key, $snippet);
	}
	
	/**
	 * Flushes all local data to common context after wakeup
	 * @param ResponseContext $mainContext
	 */
	public function flushToContext(ResponseContext $mainContext)
	{
		$this->localContext->flushToContext($mainContext);
	}
	
	/**
	 * @return ResponseContext
	 */
	public function getLocalContext()
	{
		return $this->localContext;
	}
	
	/**
	 * @param string $file
	 */
	public function addResourceFile($file)
	{
		// Don't add if doesn't exist
		if ( ! is_file($file)) {
			return;
		}
		
		$resources = array();
		$context = $this->getLocalContext();
		$offset = __CLASS__ . '$' . self::RESOURCE_FILE_OFFSET;
		
		if ($context->offsetExists($offset)) {
			$resources = $context->offsetGet($offset);
		}
		
		$resources[$file] = filemtime($file);
		
		$context->offsetSet($offset, $resources);
	}
	
	/**
	 * @return array
	 */
	public function getResourceFiles()
	{
		$offset = __CLASS__ . '$' . self::RESOURCE_FILE_OFFSET;
		$context = $this->getLocalContext();
		$resources = $context->offsetGet($offset);
		
		return $resources;
	}
	
	/**
	 * @return boolean
	 */
	public function isResourceChanged()
	{
		$files = $this->getResourceFiles();
		
		foreach ($files as $file => $mtime) {
			if ( ! is_file($file) || filemtime($file) != $mtime) {
				return true;
			}
		}
		
		return false;
	}
	
}
