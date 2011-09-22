<?php

namespace Supra\Mailer;

/**
 * Mailer
 *
 */
class Mailer extends \Swift_Mailer
{

	/**
	 * Create a new Mailer instance
	 *
	 * @param \Swift_Transport $transport
	 * @return self
	 */
	public static function newInstance(\Swift_Transport $transport)
	{
		$self = get_called_class();
		$instance = new $self($transport);
		return $instance;
	}
}
