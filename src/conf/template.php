<?php

$loader = new Twig_Loader_Filesystem(SUPRA_PATH);
$twig = new Supra\Template\Parser\Twig\Twig($loader, array(
			'cache' => SUPRA_TMP_PATH,
			'auto_reload' => true,
//	'strict_variables' => true,
		));

$twig->addExtension(new \Symfony\Bridge\Twig\Extension\FormExtension(null, array('lib/Supra/Form/view/form_supra.html.twig')));
//$twig->addExtension(new \Supra\Template\Parser\Twig\Extension\TranslationExtension());
$twig->addExtension(
		new Symfony\Bridge\Twig\Extension\TranslationExtension(
				new Symfony\Component\Translation\IdentityTranslator(
						new \Symfony\Component\Translation\MessageSelector())));

Supra\ObjectRepository\ObjectRepository::setDefaultTemplateParser($twig);
