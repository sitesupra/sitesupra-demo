<?php

namespace Supra\Cms\ContentManager\page;

use Supra\Controller\SimpleController;

/**
 * 
 */
class PageAction extends SimpleController
{

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
				0 =>
				array(
					'id' => 'inner',
					'type' => 'list',
					'allow' =>
					array(
						0 => 'html',
						1 => 'string',
						2 => 'sample',
					),
					'contents' =>
					array(
						0 =>
						array(
							'id' => 111,
							'type' => 'html',
							'value' => '<h1>HTML Ipsum Presents</h1><p><strong>Pellentesque habitant morbi tristique</strong> senectus et netus</p>',
						),
						1 =>
						array(
							'id' => 222,
							'type' => 'html',
							'value' => '<h2>Header Level 2</h2><ol><li>Lorem ipsum</li></ol>',
						),
					),
				),
				1 =>
				array(
					'id' => 'sidebar',
					'type' => 'list',
					'allow' =>
					array(
						0 => 'string',
					),
					'contents' =>
					array(
						0 =>
						array(
							'id' => 333,
							'type' => 'html',
							'value' => '<ul><li><a href="javascript://">Lorem ipsum dolor sit amet</a></li><li><a href="javascript://">Consectetuer adipiscing elit.</a></li><li><a href="javascript://">Aliquam tincidunt mauris eu risus.</a></li><li><a href="javascript://">Vestibulum auctor dapibus neque.</a></li></ul>',
						),
					),
				),
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
		$request->setLocale('en');
		$response = $controller->createResponse($request);
		$controller->prepare($request, $response);
		
		$em = $request->getDoctrineEntityManager();
		$pageDao = $em->getRepository(\Supra\Controller\Pages\Controller::PAGE_ENTITY);
		
		//FIXME: hardcoded value
		$requestPage = $pageDao->findOneById(2);
		
		$request->setRequestPage($requestPage);
		
		$controller->execute();
		
//		$response->flushToResponse($this->response);
		
		//TODO: fetch from the page controller
//		$this->response->output(file_get_contents(__DIR__ . '/sample-acme-page.html'));
		
		return $response->getOutput();
	}

}
