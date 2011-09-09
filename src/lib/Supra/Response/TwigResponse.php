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
	 * Root directory for templates
	 * @var string
	 */
	protected $templateDir;
	
	/**
	 * Output the template
	 * @param string $templateName
	 */
	public function outputTemplate($templateName)
	{
		$templateName = $this->templateDir . DIRECTORY_SEPARATOR . $templateName;
		
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

	/**
	 * Set template dir, will make it relative to supra path for Twig usage
	 * @param string $templateDir
	 * @throws Exception\RuntimeException if template dir is outside the supra path
	 */
	public function setTemplateDir($templateDir)
	{
		$supraPath = realpath(SUPRA_PATH);
		$templateDir = realpath($templateDir);
		
		if (strpos($templateDir, $supraPath) !== 0) {
			throw new Exception\RuntimeException("Template directory outside supra path is not allowed");
		}
		
		$relativePath = substr($templateDir, strlen($supraPath));
		
		$this->templateDir = $relativePath;
	}
}
