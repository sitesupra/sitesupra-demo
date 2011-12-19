<?php

namespace Supra\Response;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Loader\Loader;
use Supra\Template\Parser\Twig\Twig;
use Supra\Template\Parser\Twig\Loader\FilesystemLoaderByContext;

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
	 * @var mixed
	 */
	protected $context;
	
	/**
	 * @var Twig
	 */
	protected $twigEnvironment;
	
	/**
	 * Can set context classname or object to search for the templates there
	 * @param mixed $context
	 */
	public function __construct($context = null)
	{
		$this->context = $context;
		$this->twigEnvironment = ObjectRepository::getTemplateParser($this);
		
		if ( ! $this->twigEnvironment instanceof Twig) {
			throw new Exception\IncompatibleObject("Twig response object must have Twig template parser assigned");
		}
	}
	
	/**
	 * @return Twig
	 */
	public function getTwigEnvironment()
	{
		return $this->twigEnvironment;
	}

	/**
	 * Output the template
	 * @param string $templateName
	 */
	public function outputTemplate($templateName)
	{
		$loader = null;
		
		if ( ! is_null($this->context)) {
			$loader = new FilesystemLoaderByContext($this->context);
		}
		
		$content = $this->twigEnvironment->parseTemplate($templateName, 
				$this->templateVariables,
				$loader);

		$this->output($content);
	}
	
	/**
	 * Assign parameter for the Twig template
	 * @param string $name
	 * @param mixed $value
	 * @return TwigResponse
	 */
	public function assign($name, $value)
	{
		$this->templateVariables[$name] = $value;
		
		return $this;
	}

}
