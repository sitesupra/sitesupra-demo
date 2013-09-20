<?php

namespace Supra\Controller\Pages\Twig;

class TwigEmailEncoderFilter
{
	/**
	 * @param string $string
	 * @return string
	 */
	public function filter($string, $forceNoWrap = false)
	{
		$encoder = \Supra\Controller\Pages\Email\EmailEncoder::getInstance();
		
		$encodedString = $encoder->encode($string);
		
		if ( ! $forceNoWrap) {
			$encodedString = '<span data-email="text">' . $encodedString . '</span>';
		}
		
		return new \Twig_Markup($encodedString, 'UTF-8');
	}
}