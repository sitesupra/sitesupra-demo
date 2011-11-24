<?php

namespace Supra\Controller\Layout\Processor;

use Supra\Template\Parser\Twig\Twig;
use Twig_Loader_Filesystem;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Twig layout processor
 */
class TwigProcessor extends HtmlProcessor
{
	/**
	 * @param string $layoutSrc
	 * @return string
	 */
	protected function getContent($layoutSrc)
	{
		$filename = $this->getFileName($layoutSrc);
		$twig = ObjectRepository::getTemplateParser($this);
		/* @var $twig Twig */
		
		if ( ! $twig instanceof Twig) {
			throw new \RuntimeException("Twig layout processor expects twig template parser");
		}
		
		$loader = new Twig_Loader_Filesystem($this->layoutDir);
		$contents = $twig->parseTemplate($layoutSrc, array(), $loader);
		
		return $contents;
	}

}
