<?php

namespace Supra\Core\Templating;

use Supra\Core\Templating\Loader\TemplateLoader;

class Templating
{
	/**
	 * @var \Twig_Environment
	 */
	protected $twig;

	public function __construct()
	{
		//@todo: this is hardcode. we should move it to some templating component
		$loader = new TemplateLoader();

		$this->twig = new \Twig_Environment($loader);
	}

	public function render($template, $parameters)
	{
		return $this->twig->render($template, $parameters);
	}
}
