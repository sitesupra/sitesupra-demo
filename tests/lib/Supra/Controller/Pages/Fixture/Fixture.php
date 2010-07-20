<?php

namespace Supra\Test\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity,
		Supra\Database\Doctrine;

require_once 'PHPUnit/Extensions/OutputTestCase.php';

class Fixture extends \PHPUnit_Extensions_OutputTestCase
{
	public function testRebuild()
	{
		$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
		$metaDatas = $em->getMetadataFactory()->getAllMetadata();

		$schemaTool->dropSchema($metaDatas);
		$schemaTool->createSchema($metaDatas);
	}

	/**
	 * @depends testRebuild
	 */
	public function testFixture()
	{
		$page = new Entity\Page();

		$pageData = new Entity\PageData();
		$pageData->setLocale('en');
		$pageData->setTitle('Home');

		$pageData->setPage($page);
		$pageData->setPathPart('');

		$layout = new Entity\Layout();
		$layout->setFile('root.html');

		foreach (array('header', 'main') as $name) {
			$layoutPlaceHolder = new Entity\LayoutPlaceHolder();
			$layoutPlaceHolder->setName($name);
			$layoutPlaceHolder->setLayout($layout);
		}

		$template = new Entity\Template();
		$template->addLayout('screen', $layout);

		foreach (array('header', 'main') as $name) {
			$templatePlaceHolder = new Entity\TemplatePlaceHolder();
			$templatePlaceHolder->setName($name);
			if ($name != 'main') {
				$templatePlaceHolder->setLocked(true);
			}
			$templatePlaceHolder->setTemplate($template);

			if ($name == 'header') {
				$block = new Entity\TemplateBlock();
				$block->setComponent('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
			}

		}

		$templateData = new Entity\TemplateData();
		$templateData->setLocale('en');
		$templateData->setTemplate($template);
		$templateData->setTitle('Root template');

		$page->setTemplate($template);

		foreach (array('main') as $name) {
			$pagePlaceHolder = new Entity\PagePlaceHolder();
			$pagePlaceHolder->setName($name);
			$pagePlaceHolder->setPage($page);

			if ($name == 'main') {
				$block = new Entity\PageBlock();
				$block->setComponent('Project\Text\TextController');
				$block->setPlaceHolder($pagePlaceHolder);
			}
		}

		$em = Doctrine::getInstance()->getEntityManager();

		$em->persist($page);

		$em->flush();
	}
	
}