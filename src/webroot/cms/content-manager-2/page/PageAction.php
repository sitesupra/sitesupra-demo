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
	public function pageAction()
	{
		//TODO: Must get real controller, should be bound somehow
		$controller = new \Project\Pages\Controller();

		//FIXME: hardcoded now
		$locale = 'en';
		$media = \Supra\Controller\Pages\Entity\Layout::MEDIA_SCREEN;
		$pageId = 2;
		
		// Create special request
		$request = new \Supra\Controller\Pages\Request\RequestEdit($locale, $media);

		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);

		// Entity manager 
		$em = $request->getDoctrineEntityManager();
		$pageDao = $em->getRepository(\Supra\Controller\Pages\Request\Request::PAGE_ABSTRACT_ENTITY);

		/* @var $page \Supra\Controller\Pages\Entity\Abstraction\Page */
		$page = $pageDao->findOneById($pageId);
		
		/* @var $pageData \Supra\Controller\Pages\Entity\Abstraction\Data */
		$pageData = $page->getData($locale);
		
		$request->setRequestPageData($pageData);
		$controller->execute($request);

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
			'contents' => array()
		);
		
		$contents = array();
		$page = $request->getPage();
		$placeHolderSet = $request->getPlaceHolderSet()
				->getFinalPlaceHolders();
		$blockSet = $request->getBlockSet();
		$blockPropertySet = $request->getBlockPropertySet();
		
		/* @var $placeHolder \Supra\Controller\Pages\Entity\Abstraction\PlaceHolder */
		foreach ($placeHolderSet as $placeHolder) {
			
			$placeHolderData = array(
				'id' => $placeHolder->getName(),
				'type' => 'list',
				'locked' => ! $page->isPlaceHolderEditable($placeHolder),

				//TODO: not specified now
				'allow' => array(
					0 => 'Project_Text_TextController',
				),
				'contents' => array()
			);

			$blockSubset = $blockSet->getPlaceHolderBlockSet($placeHolder);


			/* @var $block \Supra\Controller\Pages\Entity\Abstraction\Block */
			foreach ($blockSubset as $block) {

				$blockData = array(
					'id' => $block->getId(),
					//TODO: move normalizing to somewhere else
					'type' => trim(str_replace('\\', '_', $block->getComponent()), '_'),
					'locked' => ! $page->isBlockEditable($block),
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
				
			$array['contents'][] = $placeHolderData;
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
	
	public function insertblockAction()
	{
		1+1;
		
		$array = array(
			'id' => null,
			'type' => null,
			'locked' => null,
			'properties' => array(
				'html' => array(
					'html' => null,
					'data' => array(),
				),
//				'visible' => true,
			),
			'html' => null,
		);
		
		$this->response->output(json_encode($array));
	}
//	"id": 
//	"type": "html",
//	"locked": false,
//	"properties": {
//		"html1": {
//			"html": "Aaaaaaaaaaaaa",
//			"data": {}
//		},
//		"html2": {
//			"html": "Bbbbbbbbbbbbb",
//			"data": {}
//		},
//		"visible": false
//	},
//	"html": "<div id=\"content_html_<?php echo $id; _html1\" class=\"yui3-content-inline\">\n<h2>Lorem ipsum<\/h2>\n<p>Lorem ipsum<\/p>\n<\/div>\n<br \/><small>Here ends <em>html1<\/em> and starts <em>html2<\/em> editable area<\/small><br \/><br \/>\n<div id=\"content_html_<?php echo $id; ? >_html2\" class=\"yui3-content-inline\">\n<h2>Lorem ipsum<\/h2>\n<p>Lorem ipsum<\/p>\n<\/div>"
	
}
