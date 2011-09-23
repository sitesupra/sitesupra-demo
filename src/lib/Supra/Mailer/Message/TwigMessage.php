<?php

namespace Supra\Mailer\Message;

use Supra\ObjectRepository\ObjectRepository;
use Twig_Environment;

/**
 * Message
 *
 */
class TwigMessage extends SimpleMessage
{

	/**
	 * @var Twig_Environment
	 */
	protected $twigEnvironment;


	/**
	 * Construct
	 *
	 * @param string $contentType
	 * @param string $charset 
	 */
	public function __construct($contentType = null, $charset = null)
	{
		// default template path
		$this->templatePath = 'lib' . DIRECTORY_SEPARATOR
				. 'Supra' . DIRECTORY_SEPARATOR
				. 'Mailer' . DIRECTORY_SEPARATOR
				. 'template' . DIRECTORY_SEPARATOR;
		
		$this->twigEnvironment = ObjectRepository::getObject($this, 'Twig_Environment');
		parent::__construct($contentType, $charset);
	}
	
	/**
	 * Set body
	 *
	 * @param string $template
	 * @param array $vars
	 * @param string $contentType
	 * @param string $charset
	 */
	public function setBody($template, $vars = null, $contentType = null, $charset = null)
	{
		if (empty($template)) {
			parent::setBody(null);
			return;
		}
		
		$oldLoader = $this->twigEnvironment->getLoader();
		$e = null;
		
		$loader = new \Twig_Loader_Filesystem(SUPRA_PATH . $this->templatePath);
		$this->twigEnvironment->setLoader($loader);

		if ( ! is_array($vars)) {
			$vars = array();
		}
		
		try {
			$template = $this->twigEnvironment->loadTemplate($template);
			$body = $template->render($vars);

			parent::setBody($body, $contentType, $charset);
		} catch (\Exception $e) {}
		
		$this->twigEnvironment->setLoader($oldLoader);
		
		if ( ! empty($e)) {
			throw $e;
		}			
	}
	
	/**
	 * Set template path, will make it relative to supra path for Twig usage
	 * 
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
	
}
