<?php

namespace Supra\Controller\Pages\Markup\Abstraction;

abstract class ContentElement extends ElementAbstraction
{

	/**
	 * @var string
	 */
	protected $content;

	/**
	 * @return string
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * @param string $content 
	 */
	public function setContent($content)
	{
		$this->content = $content;
	}

}
