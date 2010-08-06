<?php

namespace Supra\Tests\Controller\Pages;

use Supra\Controller\Pages;

class Mockup
{

	/**
	 * @var \Doctrine\ORM\EntityManager
	 */
	protected $entityManager;

	protected function createEmptySchema()
	{
		$tool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
		$classNames = $this->entityManager
				->getConfiguration()
				->getMetadataDriverImpl()
				->getAllClassNames();


		$classMetadata = array();
		foreach ($classNames as $className) {
			$classMetadata[] = $this->entityManager->getClassMetadata($className);
		}
		$tool->dropSchema($classMetadata);
		$tool->createSchema($classMetadata);
	}

	function __invoke(\Doctrine\ORM\EntityManager $em)
	{
		$this->entityManager = $em;
		$this->createEmptySchema();

		$baseTemplate = $this->createBaseTemplate('main.tpl', 'Main template');

		$root = new Pages\Page();

		$template = $this->createTemplate($baseTemplate, 'Template A');

		$root->setTemplate($template);

		$em->persist($root);

		$pageA = new Pages\Page();
		$pageA->setPathPart('a');

		$template = $this->createTemplate($baseTemplate);

		$pageA->setTemplate($template);
		$pageA->setParent($root);

		$em->persist($pageA);

		$em->flush();

	}

	protected function createPage()
	{
		
	}

	/**
	 * @param string $layoutFile
	 * @return Pages\Template
	 */
	protected function createBaseTemplate($layoutFile, $templateTitle)
	{
		$baseTemplate = new Pages\Template();
		$layout = new Pages\Layout();
		$layout->setFile($layoutFile);
		$baseTemplate->addLayout('screen', $layout);

		//$data = new TemplateDa

		$this->entityManager->persist($baseTemplate);
		return $baseTemplate;
	}
	
	/**
	 * @param string $layoutFile
	 * @return Pages\Template
	 */
	protected function createTemplate(Pages\Template $parentTemplate)
	{
		$template = new Pages\Template();
		$template->setParent($parentTemplate);
		$this->entityManager->persist($template);
		return $template;
	}

	protected function createPage(Pages\Template $template, Pages\Page $parent = null, $pathPart = null)
	{
		$page = new Pages\Page();
		if ( ! is_null($parent)) {
			$page->setParent($parent);
		}
		$page->setPathPart($pathPart);
		$page->setTemplate($template);
		$this->entityManager->persist($page);
		return $page;
	}
}

$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();

$mockup = new Mockup();
$mockup($em);

\Log::debug('done');