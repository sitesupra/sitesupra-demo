<?php

namespace Supra\Response;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Loader\Loader;
use Twig_Environment;

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
	 * Root directory for templates
	 * @var string
	 */
	protected $templatePath;
	
	/**
	 * @var Twig_Environment
	 */
	protected $twigEnvironment;
	
	/**
	 * Can set context classname or object to search for the templates there
	 * @param mixed $context
	 */
	public function __construct($context = null)
	{
		if ( ! is_null($context)) {
			$this->setTemplatePathByContext($context);
		}
		
		$this->twigEnvironment = ObjectRepository::getObject($this, 'Twig_Environment');
	}
	
	/**
	 * @return Twig_Environment
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
		$templateName = $this->templatePath . DIRECTORY_SEPARATOR . $templateName;
		
		$template = $this->twigEnvironment->loadTemplate($templateName);
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

	/**
	 * Set template path, will make it relative to supra path for Twig usage
	 * @param string $templatePath
	 * @throws Exception\RuntimeException if template path is outside the supra path
	 */
	public function setTemplatePath($templatePath)
	{
		$supraPath = realpath(SUPRA_PATH);
		$templatePath = realpath($templatePath);
		
		if (strpos($templatePath, $supraPath) !== 0) {
			throw new Exception\RuntimeException("Template directory outside supra path is not allowed");
		}
		
		$relativePath = substr($templatePath, strlen($supraPath));
		
		$this->templatePath = $relativePath;
	}
	
	/**
	 * Sets base template directory by context class path
	 * @param mixed $context
	 * @throws Exception\InvalidArgumentException on invalid context received
	 */
	public function setTemplatePathByContext($context)
	{
		if (is_object($context)) {
			$context = get_class($context);
		}
		
		if ( ! is_string($context)) {
			throw new Exception\InvalidArgumentException("Caller must be object or string");
		}
		
		$classPath = Loader::getInstance()->findClassPath($context);
		
		if (empty($classPath)) {
			throw new Exception\InvalidArgumentException("Caller class '$context' path was not found by autoloader");
		}
		
		$classPath = dirname($classPath);
		$this->setTemplatePath($classPath);
	}
}
