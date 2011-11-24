<?php

namespace Supra\Template\Parser\Twig;

use Twig_Environment;
use Twig_LoaderInterface;
use Twig_TemplateInterface;
use Supra\Template\Parser\TemplateParser;

/**
 * Twig environment override
 */
class Twig extends Twig_Environment implements TemplateParser
{
	/**
	 * Can pass loader to use, the old loader is backed up and restored afterwards
	 * @param string $templateName
	 * @param array $templateParameters
	 * @param Twig_LoaderInterface $loader
	 * @return string
	 */
	public function parseTemplate($templateName, array $templateParameters = array(), Twig_LoaderInterface $loader = null)
	{
		$e = null;
		$contents = null;
		$oldLoader = null;
		
		if ( ! is_null($loader)) {
			$oldLoader = $this->getLoader();

			$this->setLoader($loader);
		}

		try {
			$template = $this->loadTemplate($templateName);
			$contents = $template->render($templateParameters);
		} catch (\Exception $e) {}

		if ( ! is_null($oldLoader)) {
			$this->setLoader($oldLoader);
		}

		if ( ! empty($e)) {
			throw $e;
		}

		return $contents;
	}
	
}
