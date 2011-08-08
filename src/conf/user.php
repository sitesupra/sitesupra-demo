<?php

$userProvider = \Supra\User\UserProvider::getInstance();

$emailVailidation = new Supra\User\Validation\EmailValidation();
$nameVailidation = new Supra\User\Validation\NameValidation();

$userProvider->addValidationFilter($emailVailidation);
$userProvider->addValidationFilter($nameVailidation);