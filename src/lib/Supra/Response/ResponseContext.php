<?php

namespace Supra\Response;

use Supra\Html\HtmlTag;

class ResponseContext
{

	/**
	 * @var array
	 */
	protected $contextData;

	/**
	 * @var array
	 */
	protected $layoutSnippetResponses;

	function __construct()
	{
		$this->contextData = array();
		$this->layoutSnippetResponses = array();
	}

	/**
	 * @param string $key
	 * @param mixed $value 
	 */
	public function setValue($key, $value)
	{
		$this->contextData[$key] = $value;
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $defaultValue
	 */
	public function getValue($key, $defaultValue = null)
	{
		if (empty($this->contextData[$key])) {
			return $defaultValue;
		}

		return $this->contextData[$key];
	}

	/**
	 * @return array
	 */
	public function getAllValues()
	{
		return $this->contextData;
	}

	/**
	 * @param string $key
	 * @param TwigResponse | string $value 
	 */
	public function addToLayoutSnippet($key, $snippet)
	{
		$snippet = (string) $snippet;
		
		if (empty($this->layoutSnippetResponses[$key])) {
			$this->layoutSnippetResponses[$key] = $snippet;
		} else {
			$this->layoutSnippetResponses[$key] .= $snippet;
		}
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getLayoutSnippetContents($key)
	{
		if (isset($this->layoutSnippetResponses[$key])) {
			return $this->layoutSnippetResponses[$key];
		} else {
			return '';
		}
	}

	public function addJsToLayoutSnippet($key, $js, $type = 'text/javascript')
	{
		$js = (string) $js;

		$scriptTag = new HtmlTag('script', $js);
		$scriptTag->setAttribute('type', $type);

		$this->addToLayoutSnippet($key, $scriptTag->toHtml());
	}

	public function addJsUrlToLayoutSnippet($key, $url, $type = 'text/javascript')
	{
		$scriptTag = new HtmlTag('script', '');
		$scriptTag->setAttribute('src', $url);
		$scriptTag->setAttribute('type', $type);

		$this->addToLayoutSnippet($key, $scriptTag->toHtml());
	}

	public function addCssLinkToLayoutSnippet($key, $url)
	{
		$linkTag = new HtmlTag('link');

		$linkTag->setAttribute('rel', 'stylesheet');
		$linkTag->setAttribute('type', 'text/css');
		$linkTag->setAttribute('href', $url);
		
		$this->addToLayoutSnippet($key, $linkTag->toHtml());
	}
	
	/**
	 * Flushes all data to another response context
	 * @param ResponseContext $mainContext
	 */
	public function flushToContext(ResponseContext $mainContext)
	{
		foreach ($this->contextData as $key => $value) {
			$mainContext->setValue($key, $value);
		}
		
		foreach ($this->layoutSnippetResponses as $key => $value) {
			$mainContext->addToLayoutSnippet($key, $value);
		}
	}

}
