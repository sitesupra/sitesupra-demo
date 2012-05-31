<?php

$loader = new Twig_Loader_Filesystem(SUPRA_PATH);
$twig = new Supra\Template\Parser\Twig\Twig($loader, array(
			'cache' => SUPRA_TMP_PATH,
			'auto_reload' => true,
//	'strict_variables' => true,
		));

$extension = new \Supra\Template\Parser\Twig\Extension\FormExtension();
$twig->addExtension($extension);

Supra\ObjectRepository\ObjectRepository::setDefaultTemplateParser($twig);
