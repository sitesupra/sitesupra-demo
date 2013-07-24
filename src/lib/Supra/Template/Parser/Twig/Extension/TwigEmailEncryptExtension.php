<?php

namespace Supra\Template\Parser\Twig\Extension;

use Supra\Email\EmailEncoder;

class TwigEmailEncryptExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            'email_encrypt' => new \Twig_Filter_Function('\Supra\Template\Parser\Twig\Extension\twig_email_encrypt_filter'),
        );
    }

    public function getName()
    {
        return 'email_encrypt';
    }
}

function twig_email_encrypt_filter($email)
{
	$emailEncoder = new EmailEncoder();	
	
	return $emailEncoder->encode($email);
}