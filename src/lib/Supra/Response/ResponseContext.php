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
		$layoutSnippetResponse = $this->getLayoutSnippetResponse($key);
		$layoutSnippetResponse->output($snippet);
	}

	/**
	 * @param string $key
	 * @return HttpResponse
	 */
	public function getLayoutSnippetResponse($key)
	{
		if (empty($this->layoutSnippetResponses[$key])) {
			$this->layoutSnippetResponses[$key] = new HttpResponse();
		}

		return $this->layoutSnippetResponses[$key];
	}

	/**
	 * @param string $key
	 * @return HttpResponse
	 */
	public function getLayoutSnippetContents($key)
	{
		$response = $this->getLayoutSnippetResponse($key);

		return $response->__toString();
	}

	/**
	 * @return array
	 */
	public function getAllLayoutSnippetResponses()
	{
		return $this->layoutSnippetResponses;
	}

	public function addJsToLayoutSnippet($key, $js)
	{
		$response = $this->getLayoutSnippetResponse($key);

		$scriptTag = new HtmlTag('script');
		$scriptTag->setContent($js);

		$response->output($scriptTag->toHtml());
	}

	public function addJsUrlToLayoutSnippet($key, $url)
	{
		$response = $this->getLayoutSnippetResponse($key);

		$scriptTag = new HtmlTag('script');
		$scriptTag->setAttribute('src', $url);
		$scriptTag->forceTwoPartTag(true);

		$response->output($scriptTag->toHtml());
	}

	public function addCssLinkToLayoutSnippet($key, $url)
	{
		$response = $this->getLayoutSnippetResponse($key);

		$linkTag = new HtmlTag('link');

		$linkTag->setAttribute('rel', 'stylesheet');
		$linkTag->setAttribute('type', 'text/css');
		$linkTag->setAttribute('href', $url);

		$response->output($linkTag->toHtml());
	}

}
