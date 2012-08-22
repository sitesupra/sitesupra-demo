<?php

namespace Project\FancyBlocks\Menu\Footer;

use Project\FancyBlocks\Menu\MenuBlock;
use Supra\Editable;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Finder;
use Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement;
use Supra\Controller\Pages\Entity\Abstraction\AbstractPage;

class FooterMenuBlock extends MenuBlock
{

	public static function getPropertyDefinition()
	{
		$properties = array();

		$properties['link1'] = new Editable\Link('Menu element #1');
		$properties['link2'] = new Editable\Link('Menu element #2');
		$properties['link3'] = new Editable\Link('Menu element #3');

		return $properties;
	}

	protected function doExecute()
	{
		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */
		$response = $this->getResponse();
		/* @var $response \Supra\Response\TwigResponse */
		$link1 = $this->getPropertyValue('link1');
		$link2 = $this->getPropertyValue('link2');
		$link3 = $this->getPropertyValue('link3');

		$response->assign('items1', array());
		$response->assign('items2', array());
		$response->assign('items3', array());

		if ($link1 instanceof LinkReferencedElement) {
			$response->assign('title1', $link1->getTitle() ? $link1->getTitle() :$link1->getElementTitle());
			$response->assign('link1',  $link1->getHref());
			$response->assign('items1', $this->getItems($link1));
		}

		if ($link2 instanceof LinkReferencedElement) {
			$response->assign('title2', $link2->getTitle() ? $link2->getTitle() :$link2->getElementTitle());
			$response->assign('link2',  $link2->getHref());
			$response->assign('items2', $this->getItems($link2));
		}

		if ($link3 instanceof LinkReferencedElement) {
			$response->assign('title3', $link3->getTitle() ? $link3->getTitle() :$link3->getElementTitle());
			$response->assign('link3',  $link3->getHref());
			$response->assign('items3', $this->getItems($link3));
		}

		$response->outputTemplate('index.html.twig');
	}

	protected function getPageFinder($link)
	{
		$em = ObjectRepository::getEntityManager($this);
		$pageFinder = new Finder\PageFinder($em);

		if ( ! $link instanceof LinkReferencedElement) {
			throw new \RuntimeException('Expected LinkReferencedElement');
		}

		$pageId = $link->getPageId();

		if ( ! empty($pageId) && $link->getResource() == LinkReferencedElement::RESOURCE_PAGE) {
			try {
				$page = $em->getRepository(AbstractPage::CN())->findOneById($pageId);
			} catch (\Exception $e) {
				\Log::error('Failed to find page with id #' . $pageId . '. ', $e);
			}

			if ($page instanceof AbstractPage) {
				$pageFinder->addFilterByParent($page, 1, 1);
			}
		}

		return $pageFinder;
	}

	protected function getItems($link)
	{
		$pageFinder = $this->getPageFinder($link);

		$localizationFinder = $this->getLocalizationFinder($pageFinder);

		$qb = $localizationFinder->getQueryBuilder();
		$qb->andWhere('l.visibleInMenu = true');

		$results = $qb->getQuery()->getResult();
		$items = $this->buildStructure($results);

		return $items;
	}

}
