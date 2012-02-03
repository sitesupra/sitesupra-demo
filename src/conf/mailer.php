<?php

use Supra\ObjectRepository\ObjectRepository;

$sendmailCommand = ini_get('sendmail_path') . ' -t';
$mailTransport = new \Swift_SendmailTransport($sendmailCommand);
$mailer = new \Supra\Mailer\Mailer($mailTransport);
ObjectRepository::setDefaultMailer($mailer);
$massMail = new \Supra\Mailer\MassMail\MassMail();
ObjectRepository::setDefaultMassMail($massMail);

