<?php

namespace Supra\Controller\Layout\Processor;

use Supra\Template\Parser\Twig\Twig;
use Twig_Loader_Filesystem;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Twig\TwigSupraGlobal;
use Supra\Response\HttpResponse;
use Supra\Response\ResponseContext;

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
		$themeProvider = ObjectRepository::getThemeProvider($this);
		
		$theme = $themeProvider->getActiveTheme();
		parent::setLayoutDir($theme->getLayoutRoot());

		$twig = ObjectRepository::getTemplateParser($this);
		/* @var $twig Twig */

		if ( ! $twig instanceof Twig) {
			throw new \RuntimeException("Twig layout processor expects twig template parser");
		}

		$helper = new TwigSupraGlobal();
		ObjectRepository::setCallerParent($helper, $this);
		$helper->setRequest($this->request);

		if ($this->response instanceof HttpResponse) {
			$helper->setResponseContext($this->response->getContext());
		} else {
			$helper->setResponseContext(new ResponseContext());
		}

		$helper->setTheme($theme);

		$twig->addGlobal('supra', $helper);

		$loader = new Twig_Loader_Filesystem($this->layoutDir);
		$contents = $twig->parseTemplate($layoutSrc, array(), $loader);

		return $contents;
	}

}
