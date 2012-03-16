<?php

use Supra\ObjectRepository\ObjectRepository;

$massMail = new \Supra\Mailer\MassMail\MassMail();

//$username = 'sitesupra@videinfra.com';
//$password = 'turnover';
//
//// Create new swift connection and authenticate
//$transport = Swift_SmtpTransport::newInstance('smtp.sendgrid.net', 25);
//$transport->setUsername($username);	
//$transport->setPassword($password);
//
//$mailer = new \Supra\Mailer\Mailer($transport);
//ObjectRepository::setDefaultMailer($mailer);
//$massMail = new \Supra\Mailer\MassMail\MassMail();

ObjectRepository::setCallerParent($massMail, 'Supra\Tests');
ObjectRepository::setDefaultMassMail($massMail);
