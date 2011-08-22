<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity,
		Supra\Database\Doctrine;

/**
 * Simple fixture creation class
 */
class FixtureHelper
{
	private $connectionName;
	
	protected $headerTemplateBlock;

	protected $rootPage;
	
	protected $template;
	
	public function __construct($connectionName)
	{
		$this->connectionName = $connectionName;
	}
	
	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getEntityManager()
	{
		$supraDatabase = Doctrine::getInstance();
		$em = $supraDatabase->getEntityManager($this->connectionName);
		return $em;
	}

	/**
	 * Generates random text
	 * @return string
	 */
	protected function randomText()
	{
		$possibilities = array(
			0 => array(1 => 1, 2 => 1),
			1 => array(1 => 0.3, 2 => 1),
			2 => array(1 => 1, 2 => 0.5),
		);

		$prevType = 0;
		$txt = '';
		$letters = rand(100, 2000);
		$pow = 1;
		for ($i = 0; $i < $letters; null) {
			$chr = \chr(rand(97, 122));
			//\Log::debug("Have chosen $chr");
			if (\in_array($chr, array('e', 'y', 'u', 'i', 'o', 'a'))) {
				$type = 1;
			} else {
				$type = 2;
			}
			//\Log::debug("Type is $type");

			$possibility = $possibilities[$prevType][$type];
			if ($possibility != 1) {
				if ($possibility == 0) {
					continue;
				}
				$possibility = pow($possibility, $pow);
				//\Log::debug("Possibility is $possibility");
				$rand = \rand(0, 100) / 100;
				if ($rand > $possibility) {
					//\Log::debug("Skipping because of no luck");
					continue;
				}
			}

			$txt .= $chr;
			if ($type == $prevType) {
				$pow++;
				//\Log::debug("Increasing power to $pow");
			} else {
				$pow = 1;
				//\Log::debug("Resetting power");
			}
			$prevType = $type;
			$i++;
		}

		$list = array();
		while (strlen($txt) > 10) {
			$length = rand(5, 10);
			$list[] = substr($txt, 0, $length);
			$txt = substr($txt, $length);
		}
		if ( ! empty($txt)) {
			$list[] = $txt;
		}

		$s = array();
		while (count($list) > 0) {
			$length = rand(4, 10);
			$length = min($length, count($list));
			$s[] = \array_splice($list, 0, $length);
		}

		$txt = '<p>';
		foreach ($s as $sentence) {
			$sentence = implode(' ', $sentence);
			$sentence .= '. ';
			if (rand(0, 5) == 1) {
				$sentence .= '</p><p>';
			}
			$sentence = \ucfirst($sentence);
			$txt .= $sentence;
		}
		$txt .= '</p>';

		return $txt;
	}

	public function rebuild()
	{
		$em = $this->getEntityManager();
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
		$metaDatas = $em->getMetadataFactory()->getAllMetadata();

		$classFilter = function(\Doctrine\ORM\Mapping\ClassMetadata $classMetadata) {
			return (strpos($classMetadata->namespace, 'Supra\Controller\Pages\Entity') === 0);
		};
		$metaDatas = \array_filter($metaDatas, $classFilter);

		$schemaTool->dropSchema($metaDatas);
		$schemaTool->createSchema($metaDatas);

		Entity\Abstraction\Entity::setConnectionName($this->connectionName);
	}

	/**
	 */
	public function build()
	{
		$this->rebuild();

		$em = $this->getEntityManager();
		
		$this->template = $this->createTemplate();
		
		$rootPage = $this->createPage(0, null, $this->template->getParent());
		$em->persist($rootPage);
		$em->flush();
		$this->rootPage = $rootPage;

		$page = $this->createPage(1, $rootPage, $this->template);
		$em->persist($page);
		$em->flush();

		$page2 = $this->createPage(2, $page, $this->template);
		$em->persist($page2);
		$em->flush();
	}

	protected static $constants = array(
		0 => array(
			'title' => 'Home',
			'pathPart' => '',
		),
		1 => array(
			'title' => 'About',
			'pathPart' => 'about',
		),
		2 => array(
			'title' => 'Contacts',
			'pathPart' => 'contacts',
		),
	);

	protected function createTemplate()
	{
		$template = new Entity\Template();
		$this->getEntityManager()->persist($template);

		$layout = $this->createLayout();
		$template->addLayout('screen', $layout);

		$templateData = new Entity\TemplateData('en');
		$this->getEntityManager()->persist($templateData);
		$templateData->setTemplate($template);
		$templateData->setTitle('Root template');

		foreach (array('header', 'main', 'footer', 'sidebar') as $name) {
			$templatePlaceHolder = new Entity\TemplatePlaceHolder($name);
			$this->getEntityManager()->persist($templatePlaceHolder);
			if ($name == 'header' || $name == 'footer') {
				$templatePlaceHolder->setLocked();
			}
			$templatePlaceHolder->setTemplate($template);

			if ($name == 'header') {
				$block = new Entity\TemplateBlock();
				$this->getEntityManager()->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);
				$block->setLocale('en');

				// used later in page
				$this->headerTemplateBlock = $block;

				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->getEntityManager()->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en'));
				$blockProperty->setValue('Template Header');
			}

			if ($name == 'main') {
				$block = new Entity\TemplateBlock();
				$this->getEntityManager()->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);
				$block->setLocale('en');

				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->getEntityManager()->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en'));
				$blockProperty->setValue('Template source');
				
//				// A locked block
//				$block = new Entity\TemplateBlock();
//				$this->getEntityManager()->persist($block);
//				$block->setComponentClass('Project\Text\TextController');
//				$block->setPlaceHolder($templatePlaceHolder);
//				$block->setPosition(200);
//				$block->setLocked(true);
//				$block->setLocale('en');
//
//				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
//				$this->getEntityManager()->persist($blockProperty);
//				$blockProperty->setBlock($block);
//				$blockProperty->setData($template->getData('en'));
//				$blockProperty->setValue('Template locked block');
			}

			if ($name == 'footer') {
				$block = new Entity\TemplateBlock();
				$this->getEntityManager()->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);
				$block->setLocale('en');
				$block->setLocked();

				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->getEntityManager()->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en'));
				$blockProperty->setValue('Bye <strong>World</strong>!<br />');
			}
			
			if ($name == 'sidebar') {
				$block = new Entity\TemplateBlock();
				$this->getEntityManager()->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($templatePlaceHolder);
				$block->setPosition(100);
				$block->setLocale('en');

				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->getEntityManager()->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($template->getData('en'));
				$blockProperty->setValue('<h2>Sidebar</h2><p>' . $this->randomText() . '</p>');
			}
		}
		$this->getEntityManager()->persist($template);
		$this->getEntityManager()->flush();
		
		$childTemplate = new Entity\Template();
		
		$childTemplateData = new Entity\TemplateData('en');
		$this->getEntityManager()->persist($childTemplateData);
		$childTemplateData->setTemplate($childTemplate);
		$childTemplateData->setTitle('Child template');
		
		$templatePlaceHolder = new Entity\TemplatePlaceHolder('sidebar');
		$this->getEntityManager()->persist($templatePlaceHolder);
		$templatePlaceHolder->setTemplate($childTemplate);
		
		$templatePlaceHolder = new Entity\TemplatePlaceHolder('main');
		$this->getEntityManager()->persist($templatePlaceHolder);
		$templatePlaceHolder->setTemplate($childTemplate);
		
		// A locked block
		$block = new Entity\TemplateBlock();
		$this->getEntityManager()->persist($block);
		$block->setComponentClass('Project\Text\TextController');
		$block->setPlaceHolder($templatePlaceHolder);
		$block->setPosition(200);
		$block->setLocale('en');
		$block->setLocked(true);

		$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
		$this->getEntityManager()->persist($blockProperty);
		$blockProperty->setBlock($block);
		$blockProperty->setData($childTemplateData);
		$blockProperty->setValue('<h2>Template locked block</h2>');
		
		$this->getEntityManager()->persist($childTemplate);
		$childTemplate->moveAsLastChildOf($template);
		$this->getEntityManager()->flush();
		
		return $childTemplate;
	}

	protected function createLayout()
	{
		$layout = new Entity\Layout();
		$this->getEntityManager()->persist($layout);
		$layout->setFile('root.html');

		foreach (array('header', 'main', 'footer', 'sidebar') as $name) {
			$layoutPlaceHolder = new Entity\LayoutPlaceHolder($name);
			$layoutPlaceHolder->setLayout($layout);
		}
		return $layout;
	}

	protected function createPage($type = 0, Entity\Page $parentNode = null, Entity\Template $template = null)
	{
		$page = new Entity\Page();
		$this->getEntityManager()->persist($page);

		$page->setTemplate($template);

		if ( ! is_null($parentNode)) {
			$parentNode->addChild($page);
		}
		$this->getEntityManager()->flush();

		$pageData = new Entity\PageData('en');
		$this->getEntityManager()->persist($pageData);
		$pageData->setTitle(self::$constants[$type]['title']);

		$pageData->setPage($page);
		$pageData->setPathPart(self::$constants[$type]['pathPart']);

		$this->getEntityManager()->flush();

		foreach (array('header', 'main', 'footer') as $name) {

			if ($name == 'header') {
				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->getEntityManager()->persist($blockProperty);
				$blockProperty->setBlock($this->headerTemplateBlock);
				$blockProperty->setData($page->getData('en'));
				$blockProperty->setValue('<h1>Hello SiteSupra in page /' . $pageData->getPath() . '</h1>');
				
				$placeHolder = new Entity\PagePlaceHolder('header');
				$this->getEntityManager()->persist($placeHolder);
				$placeHolder->setMaster($page);
				
				$block = new Entity\PageBlock();
				$this->getEntityManager()->persist($block);
				$block->setComponentClass('Project\Text\TextController');
				$block->setPlaceHolder($placeHolder);
				$block->setPosition(0);
				$block->setLocale('en');
				
				$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
				$this->getEntityManager()->persist($blockProperty);
				$blockProperty->setBlock($block);
				$blockProperty->setData($pageData);
				$blockProperty->setValue('this shouldn\'t be shown');
			}

			if ($name == 'main') {
				$pagePlaceHolder = new Entity\PagePlaceHolder($name);
				$this->getEntityManager()->persist($pagePlaceHolder);
				$pagePlaceHolder->setPage($page);

				foreach (\range(1, 2) as $i) {
					$block = new Entity\PageBlock();
					$this->getEntityManager()->persist($block);
					$block->setComponentClass('Project\Text\TextController');
					$block->setPlaceHolder($pagePlaceHolder);
					// reverse order
					$block->setPosition(100 * $i);
					$block->setLocale('en');

					$blockProperty = new Entity\BlockProperty('html', 'Supra\Editable\Html');
					$this->getEntityManager()->persist($blockProperty);
					$blockProperty->setBlock($block);
					$blockProperty->setData($page->getData('en'));
					$blockProperty->setValue('<h2>Section Nr ' . $i . '</h2><p>' . $this->randomText() . '</p>');
				}
			}

		}

		return $page;
	}
}
