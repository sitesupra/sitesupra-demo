<?php

namespace Supra\Cms\ContentManager\pagecontent;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerActionController;

/**
 * Controller for page content requests
 */
class PagecontentAction extends PageManagerActionController
{
	/**
	 * Insert block action
	 */
	public function pageinsertblockAction()
	{
		//FIXME: hardcoded now
		$locale = $_GET['language'];
		$locale = 'en';
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
		
		$this->getResponse()->setResponseData($array);
	}
	
	/**
	 * Content save action
	 */
	public function saveAction()
	{
		$locale = $_POST['locale'];
		$pageId = $_POST['page_id'];
		$blockId = $_POST['block_id'];
		
		//TODO: Hardcoded
		$locale = 'en';
		
		//TODO: Fix this
		$name = 'html';
		$type = 'Supra\Editable\Html';
		$value = $_POST['properties']['html']['html'];
		
		$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();
		
		$blockPropertyEntity = \Supra\Controller\Pages\Request\Request::BLOCK_PROPERTY_ENTITY;
		
		$query = $em->createQuery("SELECT p FROM $blockPropertyEntity AS p
				JOIN p.data AS d
				JOIN d.master AS m
				JOIN p.block AS b
			WHERE m.id = ?0 AND b.id = ?1 AND d.locale = ?2 AND p.name = ?3 AND p.type = ?4");
		
		$params = array(
			$pageId,
			$blockId,
			$locale,
			$name,
			$type
		);
		
		$query->setParameters($params);
		
		/* @var $blockProperty \Supra\Controller\Pages\Entity\BlockProperty */
		$blockProperty = null;
		
		try {
			$blockProperty = $query->getSingleResult();
		} catch (\Doctrine\ORM\NoResultException $noResults) {
			
			$dataEntity = \Supra\Controller\Pages\Request\Request::DATA_ENTITY;
			$blockEntity = \Supra\Controller\Pages\Request\Request::BLOCK_ENTITY;
			
			$dataQuery = $em->createQuery("SELECT d FROM $dataEntity d
					JOIN d.master AS m
					WHERE m.id = ?0 AND d.locale = ?1");
			
			$params = array(
				$pageId, $locale
			);
			$dataQuery->setParameters($params);
			$data = $dataQuery->getSingleResult();
			
			$blockQuery = $em->createQuery("SELECT b FROM $blockEntity b
					WHERE b.id = ?0");
			
			$params = array($blockId);
			$blockQuery->setParameters($params);
			$block = $blockQuery->getSingleResult();
			
			$blockProperty = new \Supra\Controller\Pages\Entity\BlockProperty($name, $type);
			$em->persist($blockProperty);
			$blockProperty->setData($data);
			$blockProperty->setBlock($block);
		}
		
		$blockProperty->setValue($value);
		
		$em->flush();
		
		// OK response
		$this->getResponse()->setResponseData(true);
	}
	
	/**
	 * Removes the block
	 */
	public function deleteblockAction()
	{
		$blockId = $_POST['block_id'];
		
		$em = \Supra\Database\Doctrine::getInstance()
				->getEntityManager();
		$blockEntity = \Supra\Controller\Pages\Request\Request::BLOCK_ENTITY;
		$blockQuery = $em->createQuery("SELECT b FROM $blockEntity b
					WHERE b.id = ?0");
		
		$blockQuery->setParameters(array($blockId));
		$block = $blockQuery->getSingleResult();
		
		$em->remove($block);
		$em->flush();
		
		// OK response
		$this->getResponse()->setResponseData(true);
	}
	
	/**
	 * Action called on block order action
	 */
	public function orderblocksAction()
	{
		$pageId = $_POST['page_id'];
		$locale = $_POST['locale'];
		$placeHolderName = $_POST['id'];
		$blockOrder = $_POST['order'];
		$blockPositionById = array_flip($blockOrder);
		
		if (count($blockOrder) != count($blockPositionById)) {
			\Log::swarn("Block order array received contains duplicate block IDs: ", $blockOrder);
		}
		
		//TODO: hardcoded
		$locale = 'en';
		$media = \Supra\Controller\Pages\Entity\Layout::MEDIA_SCREEN;
		
//		$request = new \Supra\Controller\Pages\Request\RequestEdit($locale, $media);
		
		$em = \Supra\Database\Doctrine::getInstance()
				->getEntityManager();
//		$request->setDoctrineEntityManager($em);
		
		$pageDao = $em->getRepository('Supra\Controller\Pages\Entity\Abstraction\Page');
		
		/* @var $page \Supra\Controller\Pages\Entity\Abstraction\Page */
		$page = $pageDao->findOneById($pageId);
		$data = $page->getData($locale);
//		$request->setRequestPageData($data);
		
		/* @var $placeHolder \Supra\Controller\Pages\Entity\Abstraction\PlaceHolder */
		$placeHolder = $page->getPlaceHolders()
				->offsetGet($placeHolderName);
		
		$blocks = $placeHolder->getBlocks();
		
		$maxPosition = max($blockPositionById);
		
		/* @var $block \Supra\Controller\Pages\Entity\Abstraction\Block */
		foreach ($blocks as $block) {
			$id = $block->getId();
			
			if ( ! array_key_exists($id, $blockPositionById)) {
				$maxPosition++;
				$block->setPosition($maxPosition);
			} else {
				$block->setPosition($blockPositionById[$id]);
			}
		}
		
		$em->flush();
		
		$this->getResponse()->setResponseData(true);
	}
}
