<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity;
use Supra\Database\Doctrine;
use Supra\Log\Writer\WriterAbstraction;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;

/**
 * Simple fixture creation class
 */
class FixtureHelper
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;
	
	/**
	 * @var WriterAbstraction
	 */
	private $log;
	
	protected $headerTemplateBlocks = array();

	protected $rootPage;
	
	protected $template;
	
	private $locales = array();
	
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
		3 => array(
			'title' => 'News application',
			'pathPart' => 'news',
			'applicationId' => 'news'
		),
		4 => array(
			'title' => 'Pages',
			'pathPart' => '',
			'group' => true
		),
		5 => array(
			'title' => 'Subscribe',
			'pathPart' => 'subscribe',
		),
		6 => array(
			'title' => 'First Publication',
			'pathPart' => 'first',
		),
	);
	
	public function __construct(\Doctrine\ORM\EntityManager $em)
	{
		$this->log = ObjectRepository::getLogger($this);
		$this->entityManager = $em;
		
		$this->locales = ObjectRepository::getLocaleManager($this)
				->getLocales();
		
		// Manually load CMS config
		$parser = new \Supra\Configuration\Parser\YamlParser();
		$parser->parseFile(SUPRA_WEBROOT_PATH . 'cms/config.yml');
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
		$em = $this->entityManager;
		$schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
		$metaDatas = $em->getMetadataFactory()->getAllMetadata();

		$classFilter = function(\Doctrine\ORM\Mapping\ClassMetadata $classMetadata) {
			return (strpos($classMetadata->namespace, 'Supra\Controller\Pages\Entity') === 0);
		};
		$metaDatas = \array_filter($metaDatas, $classFilter);

		$schemaTool->dropSchema($metaDatas);
		$schemaTool->createSchema($metaDatas);
	}
	
	public function deletePages()
	{
		$publicEm = ObjectRepository::getEntityManager('');
		$draftEm = $this->entityManager;
		
		$draftEm->createQuery("DELETE FROM " . Entity\BlockProperty::CN())->execute();
		$publicEm->createQuery("DELETE FROM " . Entity\BlockProperty::CN())->execute();
		$draftEm->createQuery("DELETE FROM " . Entity\Abstraction\Block::CN())->execute();
		$publicEm->createQuery("DELETE FROM " . Entity\Abstraction\Block::CN())->execute();
		$draftEm->createQuery("DELETE FROM " . Entity\Abstraction\Localization::CN())->execute();
		$publicEm->createQuery("DELETE FROM " . Entity\Abstraction\Localization::CN())->execute();
		$draftEm->createQuery("DELETE FROM " . Entity\TemplateLayout::CN())->execute();
		$publicEm->createQuery("DELETE FROM " . Entity\TemplateLayout::CN())->execute();
		$draftEm->createQuery("DELETE FROM " . Entity\Abstraction\PlaceHolder::CN())->execute();
		$publicEm->createQuery("DELETE FROM " . Entity\Abstraction\PlaceHolder::CN())->execute();
		$draftEm->createQuery("DELETE FROM " . Entity\Abstraction\AbstractPage::CN())->execute();
		$publicEm->createQuery("DELETE FROM " . Entity\Abstraction\AbstractPage::CN())->execute();
	}

	/**
	 */
	public function build()
	{
		$this->deletePages();
		
//		$this->rebuild();
		$rootPage = $page = $page2 = null;

		$em = $this->entityManager;
		$em->beginTransaction();
		
		try {
			$this->template = $this->createTemplate();

			$rootPage = $this->createPage(0, null, $this->template->getParent());
			$this->rootPage = $rootPage;

			$page = $this->createPage(1, $rootPage, $this->template);

			$page2 = $this->createPage(2, $page, $this->template);
			
			$newsApp = $this->createPage(3, $rootPage, $this->template);
			
			$publication = $this->createPage(6, $newsApp, $this->template);
			
			$newsPages = $this->createPage(4, $newsApp, $this->template);
			
			$subscribe = $this->createPage(5, $newsPages, $this->template);
			
		} catch (\Exception $e) {
			$em->rollback();
			
			throw $e;
		}
		
		$em->commit();
		
		$publicEm = ObjectRepository::getEntityManager('');
		
		$em->beginTransaction();
		$publicEm->beginTransaction();
		
		// Strange enough this script doesn't care about publishing order much
		$pageIdList = $em->createQuery("SELECT p.id FROM " . Entity\Abstraction\AbstractPage::CN() . " p ORDER BY p.left ASC")
				->getResult(Doctrine\Hydrator\ColumnHydrator::HYDRATOR_ID);
		
		try {
			foreach ($pageIdList as $pageId) {

				$em->clear();
				$publicEm->clear();
				
				$pageToPublish = $em->find(\Supra\Controller\Pages\Request\PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
				/* @var $pageToPublish Entity\Abstraction\AbstractPage */
				
				/* @var $locale \Supra\Locale\Locale */
				foreach ($this->locales as $locale) {
					$localeId = $locale->getId();

//					$em->clear();
//					$publicEm->clear();
					
					$this->log->debug("Publishing object $pageToPublish");
					
					$localization = $pageToPublish->getLocalization($localeId);
					$request = \Supra\Controller\Pages\Request\PageRequestEdit::factory($localization);
					$request->setDoctrineEntityManager($em);
					
					// Will create missing placeholders and flush
					$request->getPlaceHolderSet();
					
					// Don't allow missing place holders to be created automatically
					$request->blockFlushing();
					
					try {
						$request->publish($publicEm);
					} catch (\Exception $e) {
						$this->log->error("Failed to publish page {$pageToPublish} in language {$localeId}");
						throw $e;
					}
				}

			}
		} catch (\Exception $e) {
			$em->rollback();
			$publicEm->rollback();
			
			throw $e;
		}
		
		$em->commit();
		$publicEm->commit();
	}

	protected function createTemplate()
	{
		$template = new Entity\Template();
		$this->entityManager->persist($template);

		$layout = $this->createLayout();
		$template->addLayout('screen', $layout);

		/* @var $locale \Supra\Locale\Locale */
		foreach ($this->locales as $locale) {
			$localeId = $locale->getId();
		
			$templateData = new Entity\TemplateLocalization($localeId);
			$this->entityManager->persist($templateData);
			$templateData->setTemplate($template);
			$templateData->setTitle('Root template');

			foreach (array('header', 'main', 'footer', 'sidebar') as $name) {

				$templatePlaceHolder = $template->getPlaceHolders()
						->get($name);

				if (empty($templatePlaceHolder)) {
					$templatePlaceHolder = new Entity\TemplatePlaceHolder($name);
					$this->entityManager->persist($templatePlaceHolder);
					if ($name == 'header' || $name == 'footer') {
						$templatePlaceHolder->setLocked();
					}
					$templatePlaceHolder->setTemplate($template);
				}

				if ($name == 'header') {
					$block = new Entity\TemplateBlock();
					$this->entityManager->persist($block);
					$block->setComponentClass('Project\Text\TextController');
					$block->setPlaceHolder($templatePlaceHolder);
					$block->setPosition(100);
					$block->setLocale($localeId);

					// used later in page
					$this->headerTemplateBlocks[$localeId] = $block;

					$blockProperty = new Entity\BlockProperty('content', 'Supra\Editable\Html');
					$this->entityManager->persist($blockProperty);
					$blockProperty->setBlock($block);
					$blockProperty->setLocalization($template->getLocalization($localeId));
					$blockProperty->setValue('Template Header');
				}

				if ($name == 'main') {
					$block = new Entity\TemplateBlock();
					$this->entityManager->persist($block);
					$block->setComponentClass('Project\Text\TextController');
					$block->setPlaceHolder($templatePlaceHolder);
					$block->setPosition(100);
					$block->setLocale($localeId);

					$blockProperty = new Entity\BlockProperty('content', 'Supra\Editable\Html');
					$this->entityManager->persist($blockProperty);
					$blockProperty->setBlock($block);
					$blockProperty->setLocalization($template->getLocalization($localeId));
					$blockProperty->setValue('Template source');

	//				// A locked block
	//				$block = new Entity\TemplateBlock();
	//				$this->entityManager->persist($block);
	//				$block->setComponentClass('Project\Text\TextController');
	//				$block->setPlaceHolder($templatePlaceHolder);
	//				$block->setPosition(200);
	//				$block->setLocked(true);
	//				$block->setLocale($localeId);
	//
	//				$blockProperty = new Entity\BlockProperty('content', 'Supra\Editable\Html');
	//				$this->entityManager->persist($blockProperty);
	//				$blockProperty->setBlock($block);
	//				$blockProperty->setLocalization($template->getLocalization($localeId));
	//				$blockProperty->setValue('Template locked block');
				}

				if ($name == 'footer') {
					$block = new Entity\TemplateBlock();
					$this->entityManager->persist($block);
					$block->setComponentClass('Project\Text\TextController');
					$block->setPlaceHolder($templatePlaceHolder);
					$block->setPosition(100);
					$block->setLocale($localeId);
					$block->setLocked();

					$blockProperty = new Entity\BlockProperty('content', 'Supra\Editable\Html');
					$this->entityManager->persist($blockProperty);
					$blockProperty->setBlock($block);
					$blockProperty->setLocalization($template->getLocalization($localeId));
					$blockProperty->setValue('Bye <strong>World</strong>!<br />');
				}

				if ($name == 'sidebar') {
					$block = new Entity\TemplateBlock();
					$this->entityManager->persist($block);
					$block->setComponentClass('Project\Text\TextController');
					$block->setPlaceHolder($templatePlaceHolder);
					$block->setPosition(100);
					$block->setLocale($localeId);

					$blockProperty = new Entity\BlockProperty('content', 'Supra\Editable\Html');
					$this->entityManager->persist($blockProperty);
					$blockProperty->setBlock($block);
					$blockProperty->setLocalization($template->getLocalization($localeId));
					$blockProperty->setValue('<p>' . $this->randomText() . '</p>');
				}
			}
		}
		
		$this->entityManager->persist($template);
		$this->entityManager->flush();

		$childTemplate = new Entity\Template();

		/* @var $locale \Supra\Locale\Locale */
		foreach ($this->locales as $locale) {
			$localeId = $locale->getId();
			
			$childTemplateLocalization = new Entity\TemplateLocalization($localeId);
			$this->entityManager->persist($childTemplateLocalization);
			$childTemplateLocalization->setTemplate($childTemplate);
			$childTemplateLocalization->setTitle('Child template');

			
			$templatePlaceHolder = $childTemplate->getPlaceHolders()
					->get('sidebar');

			if (empty($templatePlaceHolder)) {
				$templatePlaceHolder = new Entity\TemplatePlaceHolder('sidebar');
				$this->entityManager->persist($templatePlaceHolder);
				$templatePlaceHolder->setTemplate($childTemplate);
			}

			$templatePlaceHolder = $childTemplate->getPlaceHolders()
					->get('main');

			if (empty($templatePlaceHolder)) {
				$templatePlaceHolder = new Entity\TemplatePlaceHolder('main');
				$this->entityManager->persist($templatePlaceHolder);
				$templatePlaceHolder->setTemplate($childTemplate);
			}

			// A locked block
			$block = new Entity\TemplateBlock();
			$this->entityManager->persist($block);
			$block->setComponentClass('Project\Text\TextController');
			$block->setPlaceHolder($templatePlaceHolder);
			$block->setPosition(200);
			$block->setLocale($localeId);
			$block->setLocked(true);

			$blockProperty = new Entity\BlockProperty('content', 'Supra\Editable\Html');
			$this->entityManager->persist($blockProperty);
			$blockProperty->setBlock($block);
			$blockProperty->setLocalization($childTemplateLocalization);
			$blockProperty->setValue('<em>Template locked block</em>');
		}
		
		$this->entityManager->persist($childTemplate);
		$childTemplate->moveAsLastChildOf($template);
		$this->entityManager->flush();
		
		return $childTemplate;
	}

	protected function createLayout()
	{
		$layout = new Entity\Layout();
		$this->entityManager->persist($layout);
		$layout->setFile('root.html');

		foreach (array('header', 'main', 'footer', 'sidebar') as $name) {
			$layoutPlaceHolder = new Entity\LayoutPlaceHolder($name);
			$layoutPlaceHolder->setLayout($layout);
		}
		return $layout;
	}

	protected function createPage($type = 0, Entity\Abstraction\AbstractPage $parentNode = null, Entity\Template $template = null)
	{
		$pageDefinition = self::$constants[$type];
		$page = null;
		
		// Application
		if ( ! empty($pageDefinition['applicationId'])) {
			$page = new Entity\ApplicationPage();
			$page->setApplicationId($pageDefinition['applicationId']);
			$this->entityManager->persist($page);
			
		// Group
		} elseif ( ! empty($pageDefinition['group'])) {
			$page = new Entity\GroupPage();
			$page->setTitle($pageDefinition['title']);
			$this->entityManager->persist($page);
			
		// Standard page
		} else {
			$page = new Entity\Page();
			$this->entityManager->persist($page);
		}

		if ( ! is_null($parentNode)) {
			$parentNode->addChild($page);
		}
		$this->entityManager->flush();

		// No localization for group page
		if ( ! $page instanceof Entity\GroupPage) {
			/* @var $locale \Supra\Locale\Locale */
			foreach ($this->locales as $locale) {
				$localeId = $locale->getId();

				if ($page instanceof Entity\ApplicationPage) {
					$pageData = new Entity\ApplicationLocalization($localeId);
				} else {
					$pageData = new Entity\PageLocalization($localeId);
				}
				$pageData->setTemplate($template);
				$this->entityManager->persist($pageData);
				$pageData->setTitle($pageDefinition['title']);

				$pageData->setPage($page);

				$this->entityManager->flush();

				// Path is generated on updates ONLY!
				$pageData->setPathPart($pageDefinition['pathPart']);
				$this->entityManager->flush();

				foreach (array('header', 'main', 'footer') as $name) {

					if ($name == 'header') {
						$blockProperty = new Entity\BlockProperty('title', 'Supra\Editable\String');
						$this->entityManager->persist($blockProperty);
						$blockProperty->setBlock($this->headerTemplateBlocks[$localeId]);
						$blockProperty->setLocalization($page->getLocalization($localeId));
						$blockProperty->setValue($pageDefinition['title']);

						$placeHolder = $page->getPlaceHolders()
								->get($name);

						if (empty($placeHolder)) {
							$placeHolder = new Entity\PagePlaceHolder($name);
							$this->entityManager->persist($placeHolder);
							$placeHolder->setMaster($page);
						}

						$block = new Entity\PageBlock();
						$this->entityManager->persist($block);
						$block->setComponentClass('Project\Text\TextController');
						$block->setPlaceHolder($placeHolder);
						$block->setPosition(0);
						$block->setLocale($localeId);

						$blockProperty = new Entity\BlockProperty('content', 'Supra\Editable\Html');
						$this->entityManager->persist($blockProperty);
						$blockProperty->setBlock($block);
						$blockProperty->setLocalization($pageData);
						$blockProperty->setValue('this shouldn\'t be shown');
					}

					if ($name == 'main') {

						$placeHolder = $page->getPlaceHolders()
								->get($name);

						if (empty($placeHolder)) {
							$pagePlaceHolder = new Entity\PagePlaceHolder($name);
							$this->entityManager->persist($pagePlaceHolder);
							$pagePlaceHolder->setPage($page);
						}

						foreach (\range(1, 2) as $i) {
							$block = new Entity\PageBlock();
							$this->entityManager->persist($block);
							$block->setComponentClass('Project\Text\TextController');
							$block->setPlaceHolder($pagePlaceHolder);
							// reverse order
							$block->setPosition(100 * $i);
							$block->setLocale($localeId);

							$blockProperty = new Entity\BlockProperty('content', 'Supra\Editable\Html');
							$this->entityManager->persist($blockProperty);
							$blockProperty->setBlock($block);
							$blockProperty->setLocalization($page->getLocalization($localeId));
							$blockProperty->setValue('<p>' . $this->randomText() . '</p>');
						}
					}

				}
			}
		}
		
		$this->entityManager->flush();

		return $page;
	}
}
