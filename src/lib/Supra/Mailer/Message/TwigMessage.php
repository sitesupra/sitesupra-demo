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
		
		$templatePath = SUPRA_LIBRARY_PATH . 'Supra' . DIRECTORY_SEPARATOR
				. 'Mailer' . DIRECTORY_SEPARATOR
				. 'template' . DIRECTORY_SEPARATOR;
		$loader = new \Twig_Loader_Filesystem($templatePath);
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
	
}
