<?php

use Supra\ObjectRepository\ObjectRepository;

$massMail = new \Supra\Mailer\MassMail\MassMail();
ObjectRepository::setCallerParent($massMail, 'Supra\Tests');
ObjectRepository::setDefaultMassMail($massMail);
