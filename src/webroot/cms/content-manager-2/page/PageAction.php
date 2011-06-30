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
		$array = array(
			'id' => 111,
			'title' => 'Catalogue',
			'path' => 'catalogue',
			'path_prefix' => '/sample/',
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
			'internal_html' => $this->previewAction(),
			'contents' =>
			array(
				array(
					'id' => 'main',
					'type' => 'list',
					'allow' =>
					array(
						0 => 'html',
						1 => 'string',
						2 => 'sample',
					),
					'contents' =>
					array(
						array(
							'id' => 5,
							'type' => 'Project_Text_TextController',
//							'type' => 'html',
							'properties' => 
							array(
								'html' => 
								array(
									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
									'data' => array()
								),
//								'html2' => 
//								array(
//									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
//									'data' => array()
//								),
//								'visible' => true,
							),
						),
						array(
							'id' => 6,
							'type' => 'Project_Text_TextController',
							'properties' => 
							array(
								'html' => 
								array(
									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
									'data' => array()
								),
							),
						),
						array(
							'id' => 7,
							'type' => 'Project_Text_TextController',
							'properties' => 
							array(
								'html' => 
								array(
									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
									'data' => array()
								),
							),
						),
						array(
							'id' => 8,
							'type' => 'Project_Text_TextController',
							'properties' => 
							array(
								'html' => 
								array(
									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
									'data' => array()
								),
							),
						),
						array(
							'id' => 9,
							'type' => 'Project_Text_TextController',
							'properties' => 
							array(
								'html' => 
								array(
									'html' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
									'data' => array()
								),
							),
						),
//						array(
//							'id' => 6,
//							'type' => 'html',
//							'properties' => 
//							array(
//								'html1' => 
//								array(
//									'html' => '<h2>Header Level 2</h2><ol><li>Lorem ipsum</li></ol>',
//									'data' => array()
//								),
//								'html2' => 
//								array(
//									'html' => '<h2>Header Level 2</h2><ol><li>Lorem ipsum</li></ol>',
//									'data' => array()
//								),
//								'visible' => true,
//							),
//						),
					),
				),
//				array(
//					'id' => 'sidebar',
//					'type' => 'list',
//					'allow' =>
//					array(
//						0 => 'string',
//					),
//					'contents' =>
//					array(
//						array(
//							'id' => 7,
//							'type' => 'html',
//							'properties' => 
//							array(
//								'html1' => 
//								array(
//									'html' => '<ul><li><a href="javascript://">Lorem ipsum dolor sit amet</a></li><li><a href="javascript://">Consectetuer adipiscing elit.</a></li><li><a href="javascript://">Aliquam tincidunt mauris eu risus.</a></li><li><a href="javascript://">Vestibulum auctor dapibus neque.</a></li></ul>',
//									'data' => array()
//								),
//								'html2' => 
//								array(
//									'html' => '',
//									'data' => array()
//								),
//								'visible' => true,
//							),
//						),
//					),
//				),
			),
		);

		// TODO: json encoding must be already inside the manager action response object
		$this->response->output(json_encode($array));
	}

	private function previewAction()
	{
		//TODO: Must get real controller, should be bound somehow
		$controller = new \Project\Pages\Controller();

		$request = new \Supra\Controller\Pages\Request\RequestEdit();

		//FIXME: hardcoded now
		$locale = 'en';
		$request->setLocale($locale);
		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);

		$em = $request->getDoctrineEntityManager();
		$pageDao = $em->getRepository(\Supra\Controller\Pages\Controller::PAGE_ENTITY);

		//FIXME: hardcoded value
		/* @var $requestPage \Supra\Controller\Pages\Entity\Abstraction\Page */
		$requestPage = $pageDao->findOneById(2);
		$requestPageData = $requestPage->getData($locale);

		$request->setRequestPageData($requestPageData);

		$controller->execute();

//		$response->flushToResponse($this->response);
		//TODO: fetch from the page controller
//		$this->response->output(file_get_contents(__DIR__ . '/sample-acme-page.html'));

		return $response->getOutput();
	}

}
