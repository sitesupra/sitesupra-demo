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
		
		$em = Doctrine::getInstance()->getEntityManager();
		$em->persist($page);

		$pageData = new Entity\PageData('en');
		$pageData->setTitle('Home');

		$pageData->setPage($page);
		$pageData->setPathPart('');

		$layout = new Entity\Layout();
		$layout->setFile('root.html');

		foreach (array('header', 'main', 'footer') as $name) {
			$layoutPlaceHolder = new Entity\LayoutPlaceHolder($name);
			$layoutPlaceHolder->setLayout($layout);
		}

		$template = new Entity\Template();
		$template->addLayout('screen', $layout);

		$templateData = new Entity\TemplateData('en');
		$templateData->setTemplate($template);
		$templateData->setTitle('Root template');

		foreach (array('header', 'main', 'footer') as $name) {
			$templatePlaceHolder = new Entity\TemplatePlaceHolder($name);
			if ($name != 'main') {
				$templatePlaceHolder->setLocked(true);
			}
			$templatePlaceHolder->setTemplate($template);

			if ($name == 'header') {
				$block = new Entity\TemplateBlock();
				$block->setComponent('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);

				$headerTemplateBlock = $block;

				$blockProperty = new Entity\Abstraction\BlockProperty('html');
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en'));
				$blockProperty->setValue('Template Header');
			}

			if ($name == 'main') {
				$block = new Entity\TemplateBlock();
				$block->setComponent('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);

				$blockProperty = new Entity\Abstraction\BlockProperty('html');
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en'));
				$blockProperty->setValue('Template source');
			}

			if ($name == 'footer') {
				$block = new Entity\TemplateBlock();
				$block->setComponent('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);
				$block->setLocked();

				$blockProperty = new Entity\Abstraction\BlockProperty('html');
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en'));
				$blockProperty->setValue('Bye <strong>World</strong>! <small>(Template value)</small>');
			}
		}

		$page->setTemplate($template);

		foreach (array('header', 'main', 'footer') as $name) {
			$pagePlaceHolder = new Entity\PagePlaceHolder($name);
			$pagePlaceHolder->setPage($page);

			if ($name == 'header') {
				// this won't be read because template's place holder is locked
				$block = new Entity\PageBlock();
				$block->setComponent('Project\Text\TextController');
				$block->setPlaceHolder($pagePlaceHolder);
				$block->setPosition(100);

				$blockProperty = new Entity\Abstraction\BlockProperty('html');
				$blockProperty->setBlock($headerTemplateBlock);
				$blockProperty->setData($page->getData('en'));
				$blockProperty->setValue('Page Header');
			}

			if ($name == 'main') {
				$block = new Entity\PageBlock();
				$block->setComponent('Project\Text\TextController');
				$block->setPlaceHolder($pagePlaceHolder);
				$block->setPosition(100);

				$blockProperty = new Entity\Abstraction\BlockProperty('html');
				$blockProperty->setBlock($block);
				$blockProperty->setData($page->getData('en'));
				$blockProperty->setValue('Page source');
			}

			if ($name == 'footer') {
				$block = new Entity\PageBlock();
				$block->setComponent('Project\Text\TextController');
				$block->setPlaceHolder($pagePlaceHolder);
				$block->setPosition(100);

				$blockProperty = new Entity\Abstraction\BlockProperty('html');
				$blockProperty->setBlock($block);
				$blockProperty->setData($page->getData('en'));
				$blockProperty->setValue('Bye <strong>World</strong>! <small>(Page value)</small>');
			}
		}

		$em->flush();
	}
	
}