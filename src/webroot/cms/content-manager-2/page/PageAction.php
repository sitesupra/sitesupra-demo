<?php

namespace Supra\Cms\ContentManager\page;

use Supra\Controller\SimpleController;

/**
 * 
 */
class PageAction extends SimpleController
{

	/**
	 * @return string
	 */
	public function indexAction()
	{
		//TODO: Must get real controller, should be bound somehow
		$controller = new \Project\Pages\Controller();

		$request = new \Supra\Controller\Pages\Request\RequestEdit();

		//FIXME: hardcoded now
		$locale = 'en';
		$media = \Supra\Controller\Pages\Entity\Layout::MEDIA_SCREEN;
		$request->setLocale($locale);
		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);

		$em = $request->getDoctrineEntityManager();
		$pageDao = $em->getRepository(\Supra\Controller\Pages\Set\RequestSet::PAGE_ENTITY);

		//FIXME: hardcoded value
		/* @var $page \Supra\Controller\Pages\Entity\Abstraction\Page */
		$page = $pageDao->findOneById(2);
		/* @var $pageData \Supra\Controller\Pages\Entity\Abstraction\Data */
		$pageData = $page->getData($locale);
		
		$requestSet = new \Supra\Controller\Pages\Set\RequestSet($locale, $media);
		$requestSet->setDoctrineEntityManager($em);
		$requestSet->setPage($page);

		$request->setRequestPageData($pageData);

		$controller->processRequestSet($requestSet);

//		$response->flushToResponse($this->response);
		//TODO: fetch from the page controller
//		$this->response->output(file_get_contents(__DIR__ . '/sample-acme-page.html'));
		
		$pathPart = null;
		$pathPrefix = null;
		
		//TODO: create some path for templates also
		if ($pageData instanceof \Supra\Controller\Pages\Entity\PageData) {
			$pathPart = $pageData->getPathPart();
			
			if ($page->hasParent()) {
				$pathPrefix = $page->getParent()
						->getPath();
			}
		}
		
		$array = array(
			'id' => $page->getId(),
			'title' => $pageData->getTitle(),
			'path' => $pathPart,
			'path_prefix' => $pathPrefix,
			'keywords' => 'web development, web design, nearshore development, e-commerce, visualization, 3D, web 2.0, PHP, LAMP, SiteSupra Platform, CMS, content management, web application, Web systems, IT solutions, usability improvements, system design, FMS, SFS, design conception, design solutions, intranet systems development, extranet systems development, flash development, hitask',
			'description' => '',
			'template' =>
			array(
				'id' => 'template_3',
				'title' => 'Simple',
				'img' => '/cms/supra/img/templates/template-3.png',
			),
			'scheduled_date' => '18.08.2011',
			'scheduled_time' => '08:00',
			'version' =>
			array(
				'id' => 222,
				'title' => 'Draft (auto-saved)',
				'author' => 'Admin',
				'date' => '21.05.2011',
			),
			'active' => true,
			'internal_html' => $response->getOutput(),
			'contents' =>
			array()
		);
		
		$contents = array();
		$page = $requestSet->getPage();
		$placeHolderSet = $requestSet->getPlaceHolderSet()
				->getFinalPlaceHolders();
		$blockSet = $requestSet->getBlockSet();
		$blockPropertySet = $requestSet->getBlockPropertySet();
		
		/* @var $placeHolder \Supra\Controller\Pages\Entity\Abstraction\PlaceHolder */
		foreach ($placeHolderSet as $placeHolder) {
			
			//TODO: specify if place holder is menegeable
//			if ($page->isPlaceHolderEditable($placeHolder))
			{
				
				$placeHolderData = array(
					'id' => $placeHolder->getName(),
					'type' => 'list',
					
					//TODO: not specified now
					'allow' => array(
						0 => 'html',
						1 => 'string',
						2 => 'sample',
					),
					'contents' => array()
				);
				
				$blockSubset = $blockSet->getPlaceHolderBlockSet($placeHolder);
				
				
				/* @var $block \Supra\Controller\Pages\Entity\Abstraction\Block */
				foreach ($blockSubset as $block) {
					
					//TODO: must specify somehow if block is manageable
					if ($page->isBlockEditable($block)) {
						$blockData = array(
							'id' => $block->getId(),
							//TODO: move normalizing to somewhere else
							'type' => trim(str_replace('\\', '_', $block->getComponent()), '_'),
							'properties' => array(),
						);
						
						$blockPropertySubset = $blockPropertySet->getBlockPropertySet($block);
						
						/* @var $blockProperty \Supra\Controller\Pages\Entity\BlockProperty */
						foreach ($blockPropertySubset as $blockProperty) {
							if ($page->isBlockPropertyEditable($blockProperty)) {
								$propertyData = array(
									$blockProperty->getName() => array(
										'html' => $blockProperty->getValue(),
										'data' => array()
									),
								);
								
								$blockData['properties'][] = $propertyData;
							}
						}
						
						$placeHolderData['contents'][] = $blockData;
					}
				}
				
				$array['contents'][] = $placeHolderData;
			}
		}
		
		
//				array(
//					'id' => 'main',
//					'type' => 'list',
//					'allow' =>
//					array(
//						0 => 'html',
//						1 => 'string',
//						2 => 'sample',
//					),
//					'contents' =>
//					array(
//						array(
//							'id' => 5,
//							'type' => 'Project_Text_TextController',
////							'type' => 'html',
//							'properties' => 
//							array(
//								'html' => 
//								array(
//									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
//									'data' => array()
//								),
////								'html2' => 
////								array(
////									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
////									'data' => array()
////								),
////								'visible' => true,
//							),
//						),
//						array(
//							'id' => 6,
//							'type' => 'Project_Text_TextController',
//							'properties' => 
//							array(
//								'html' => 
//								array(
//									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
//									'data' => array()
//								),
//							),
//						),
//						array(
//							'id' => 7,
//							'type' => 'Project_Text_TextController',
//							'properties' => 
//							array(
//								'html' => 
//								array(
//									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
//									'data' => array()
//								),
//							),
//						),
//						array(
//							'id' => 8,
//							'type' => 'Project_Text_TextController',
//							'properties' => 
//							array(
//								'html' => 
//								array(
//									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
//									'data' => array()
//								),
//							),
//						),
//						array(
//							'id' => 9,
//							'type' => 'Project_Text_TextController',
//							'properties' => 
//							array(
//								'html' => 
//								array(
//									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
//									'data' => array()
//								),
//							),
//						),
////						array(
////							'id' => 6,
////							'type' => 'html',
////							'properties' => 
////							array(
////								'html1' => 
////								array(
////									'html' => '<h2>Header Level 2</h2><ol><li>Lorem ipsum</li></ol>',
////									'data' => array()
////								),
////								'html2' => 
////								array(
////									'html' => '<h2>Header Level 2</h2><ol><li>Lorem ipsum</li></ol>',
////									'data' => array()
////								),
////								'visible' => true,
////							),
////						),
//					),
//				),
////				array(
////					'id' => 'sidebar',
////					'type' => 'list',
////					'allow' =>
////					array(
////						0 => 'string',
////					),
////					'contents' =>
////					array(
////						array(
////							'id' => 7,
////							'type' => 'html',
////							'properties' => 
////							array(
////								'html1' => 
////								array(
////									'html' => '<ul><li><a href="javascript://">Lorem ipsum dolor sit amet</a></li><li><a href="javascript://">Consectetuer adipiscing elit.</a></li><li><a href="javascript://">Aliquam tincidunt mauris eu risus.</a></li><li><a href="javascript://">Vestibulum auctor dapibus neque.</a></li></ul>',
////									'data' => array()
////								),
////								'html2' => 
////								array(
////									'html' => '',
////									'data' => array()
////								),
////								'visible' => true,
////							),
////						),
////					),
////				),
//			),
//		);

		// TODO: json encoding must be already inside the manager action response object
		$this->response->output(json_encode($array));
	}
	
}
