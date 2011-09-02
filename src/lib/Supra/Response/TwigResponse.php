<?php

namespace Supra\Response;

use Supra\ObjectRepository\ObjectRepository;

/**
 * Response based on Twig template parser
 */
class TwigResponse extends HttpResponse
{
	/**
	 * @var array
	 */
	protected $templateVariables = array();
	
	/**
	 * Output the template
	 * @param string $templateName
	 */
	public function outputTemplate($templateName)
	{
		$twig = ObjectRepository::getObject($this, 'Twig_Environment');
		$template = $twig->loadTemplate($templateName);
		$content = $template->render($this->templateVariables);
		
		$this->output($content);
	}
	
	/**
	 * Assign parameter for the Twig template
	 * @param string $name
	 * @param mixed $value
	 */
	public function assign($name, $value)
	{
		$this->templateVariables[$name] = $value;
	}
}
