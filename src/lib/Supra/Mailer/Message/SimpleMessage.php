<?php

namespace Supra\Mailer\Message;

/**
 * Message
 *
 */
class SimpleMessage extends \Swift_Message
{

	/**
	 * Construct
	 *
	 * @param string $contentType
	 * @param string $charset 
	 */
	public function __construct($contentType = null, $charset = null)
	{
		parent::__construct(null, null, $contentType, $charset);
	}
	
	/**
	 * Create new Message instance
	 *
	 * @param string $contentType
	 * @param string $charset
	 * @return self
	 */
	public static function newInstance($contentType = null, $charset = null) 
	{
		$self = get_called_class();
		$instance = new $self($contentType, $charset);
		return $instance;
	}
	
}
