<?php

$loader = new Twig_Loader_Filesystem(SUPRA_PATH);
$twig = new Twig_Environment($loader, array(
	'cache' => SUPRA_TMP_PATH,
	'auto_reload' => true,
//	'strict_variables' => true,
));

Supra\ObjectRepository\ObjectRepository::setDefaultObject($twig, 'Twig_Environment');
