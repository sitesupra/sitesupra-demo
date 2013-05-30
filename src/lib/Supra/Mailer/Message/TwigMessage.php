<?php

namespace Supra\Mailer\Message;

use Supra\ObjectRepository\ObjectRepository;
use Twig_Environment;
use Supra\Mailer\Exception;
use Supra\Template\Parser\Twig\Loader\FilesystemLoaderByContext;
use Supra\Template\Parser\Twig\Twig;
use Supra\Configuration\Exception\ConfigurationMissing;

/**
 * Message
 */
class TwigMessage extends SimpleMessage
{
	/**
	 * Template path context
	 * @var mixed
	 */
	protected $context;
	
	/**
	 * @var Twig
	 */
	protected $twig;

	/**
	 * Construct
	 *
	 * @param string $contentType
	 * @param string $charset 
	 */
	public function __construct($contentType = null, $charset = null)
	{
		parent::__construct($contentType, $charset);
		
		$this->twig = ObjectRepository::getTemplateParser($this);
		
		$host = ObjectRepository::getSystemInfo($this)->getHostName();	
		
		$ini = ObjectRepository::getIniConfigurationLoader($this);
		
		$defaultEmail = $ini->getValue('mail', 'default_email', "no-reply@{$host}");
		$defaultName = $ini->getValue('mail', 'default_name', null);

		$this->setFrom($defaultEmail, $defaultName);
		
		//TODO: move this validation to ObjectRepository
		if ( ! $this->twig instanceof Twig) {
			throw new \RuntimeException("Twig mail message object expects Twig template parser");
		}
	}

	/**
	 * @param mixed $context
	 */
	public function setContext($context)
	{
		$this->context = $context;
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
		
		$loader = new FilesystemLoaderByContext($this->context, $this->twig->getLoader());
		$body = $this->twig->parseTemplate($template, $vars, $loader);
		
		parent::setBody($body, $contentType, $charset);
	}
	
}
