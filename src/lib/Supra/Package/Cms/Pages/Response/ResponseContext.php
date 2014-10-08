<?php

namespace Supra\Package\Cms\Pages\Response;

use Supra\Validator\FilteredInput;
use Supra\Html\HtmlTag;

class ResponseContext extends FilteredInput
{
	/**
	 * @var array
	 */
	protected $layoutSnippetResponses = array();

	public function __construct($iterator = array())
	{
		parent::__construct($iterator);
		$this->layoutSnippetResponses = array();
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setValue($key, $value)
	{
		$this[$key] = $value;

		return $this;
	}

	/**
	 *
	 * @param string $key
	 * @param mixed $defaultValue
	 */
	public function getValue($key, $defaultValue = null)
	{
		if (empty($this[$key])) {
			return $defaultValue;
		}

		return $this[$key];
	}



	/**
	 * @return array
	 */
	public function getAllValues()
	{
		return $this->getArrayCopy();
	}

	/**
	 * @param string $key
	 * @param TwigResponse | string $value
	 */
	public function addToLayoutSnippet($key, $snippet)
	{
		$snippet = (string) $snippet;

		$hash = md5($key . '_' . $snippet);

		if (empty($this->layoutSnippetResponses[$key])) {
			$this->layoutSnippetResponses[$key] = array();
		}

		if ( ! isset($this->layoutSnippetResponses[$key][$hash])) {
			$this->layoutSnippetResponses[$key][$hash] = $snippet;
		}

		return $this;
	}

	/**
	 * @param string $key
	 * @return string
	 */
	public function getLayoutSnippetContents($key)
	{
		if (isset($this->layoutSnippetResponses[$key])) {
			return implode('', $this->layoutSnippetResponses[$key]);
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

		return $this;
	}

	public function addJsUrlToLayoutSnippet($key, $url, $type = 'text/javascript')
	{
		$scriptTag = new HtmlTag('script', '');
		$scriptTag->setAttribute('src', $url);
		$scriptTag->setAttribute('type', $type);

		$this->addToLayoutSnippet($key, $scriptTag->toHtml());

		return $this;
	}

	public function addCssLinkToLayoutSnippet($key, $url)
	{
		$linkTag = new HtmlTag('link');

		$linkTag->setAttribute('rel', 'stylesheet');
		$linkTag->setAttribute('type', 'text/css');
		$linkTag->setAttribute('href', $url);

		$this->addToLayoutSnippet($key, $linkTag->toHtml());

		return $this;
	}

	/**
	 * Flushes all data to another response context
	 * @param ResponseContext $mainContext
	 */
	public function flushToContext(ResponseContext $mainContext)
	{
		foreach ($this->getAllValues() as $key => $value) {
			$mainContext->setValue($key, $value);
		}

		foreach ($this->layoutSnippetResponses as $key => $responses) {
			foreach	($responses as $snippet) {
				$mainContext->addToLayoutSnippet($key, $snippet);
			}
		}
	}
}
