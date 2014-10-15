<?php

namespace Supra\Package\Cms\Html;

/**
 * Html tag object
 */
class HtmlTag extends HtmlTagStart
{

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @var HtmlTagEnd
	 */
	private $endTag;
	protected $forceTwoPartTag = false;

	/**
	 * @param string $tagName
	 * @param string $content
	 */
	public function __construct($tagName, $content = null)
	{
		parent::__construct($tagName);

		$this->endTag = new HtmlTagEnd($tagName);

		$this->setContent($content);
	}

	public function forceTwoPartTag($forceTwoPartTag)
	{
		$this->forceTwoPartTag = $forceTwoPartTag;
	}

	/**
	 * @param string $content
	 */
	public function setContent($content)
	{
		$this->content = $content;
		
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @return string
	 */
	public function toHtml()
	{
		$html = null;

		if ( ! is_null($this->content) || $this->forceTwoPartTag) {
			$html = $this->getHtmlForOpenTag() . $this->content . $this->endTag->toHtml();
		} else {
			$html = $this->getHtmlForClosedTag();
		}

		return $html;
	}

}
