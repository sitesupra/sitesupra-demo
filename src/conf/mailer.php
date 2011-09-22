<?php

use Supra\ObjectRepository\ObjectRepository;

$sendmailCommand = ini_get('sendmail_path') . ' -t';
$mailTransport = \Swift_SendmailTransport::newInstance($sendmailCommand);
$mailer = new \Supra\Mailer\Mailer($mailTransport);
ObjectRepository::setDefaultMailer($mailer);


