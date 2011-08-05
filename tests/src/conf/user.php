<?php

$userProvider = \Supra\User\UserProvider::getInstance();

$emailVailidation = new Supra\User\Validation\EmailValidation();

$userProvider->addValidationFilter($emailVailidation);