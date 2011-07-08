<?php

namespace Supra\Cms\ContentManager\pagecontent;

use Supra\Controller\SimpleController;

/**
 * 
 */
class PagecontentAction extends SimpleController
{
	public function insertblockAction()
	{
		//FIXME: hardcoded now
		$locale = $_GET['locale'];
		$media = \Supra\Controller\Pages\Entity\Layout::MEDIA_SCREEN;
		$pageId = $_GET['page_id'];
		$placeHolderName = $_GET['placeholder_id'];
		$blockType = $_GET['type'];
		
		$request = new \Supra\Controller\Pages\Request\RequestEdit($locale, $media);
		
		$em = \Supra\Database\Doctrine::getInstance()
				->getEntityManager();
		$request->setDoctrineEntityManager($em);
		
		$pageDao = $em->getRepository('Supra\Controller\Pages\Entity\Abstraction\Page');
		
		/* @var $page \Supra\Controller\Pages\Entity\Abstraction\Page */
		$page = $pageDao->findOneById($pageId);
		$data = $page->getData($locale);
		$request->setRequestPageData($data);
		
		/* @var $placeHolder \Supra\Controller\Pages\Entity\Abstraction\PlaceHolder */
		$placeHolder = $request->getPage()
				->getPlaceHolders()
				->get($placeHolderName);
		
		//TODO: create some factory
		$block = null;
		if ($page instanceof \Supra\Controller\Pages\Entity\Page) {
			$block = new \Supra\Controller\Pages\Entity\PageBlock();
		} else {
			$block = new \Supra\Controller\Pages\Entity\TemplateBlock();
		}
		
		//TODO: some component name normalization
		$component = str_replace('_', '\\', $blockType);
		$block->setComponent($component);
		$block->setPlaceHolder($placeHolder);
		$block->setPosition($placeHolder->getMaxBlockPosition() + 1);
		
		$em->persist($block);
		$em->flush();

		$controller = $block->createController();
		$block->prepareController($controller, $request);
		$block->executeController($controller);
		$response = $controller->getResponse();
		
		// TODO: create automatically
		$array = array(
			'id' => $block->getId(),
			'type' => $blockType,
			'locked' => false,
			'properties' => array(
				'html' => array(
					'html' => null,
					'data' => array(),
				),
//				'visible' => true,
			),
			'html' => $response->getOutput(),
		);
		
		$this->response->output(json_encode($array));
	}
}
