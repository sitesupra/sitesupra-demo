<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction;
use Supra\Controller\Response;
use Supra\Controller\Request;
use Supra\Controller\Pages\Exception;
use Doctrine\ORM\PersistentCollection;

/**
 * Page controller
 */
class Controller extends ControllerAbstraction
{

	/**
	 * Page class to be used
	 * @var string
	 */
	protected $pageEntityName = 'Supra\\Controller\\Pages\\Page';

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getDoctrineEntityManager()
	{
		$em = \Supra\Database\Doctrine::getInstance()->getEntityManager();
		return $em;
	}

	public function getPageEntityName()
	{
		return $this->pageEntityName;
	}

	public function setPageEntityName($pageEntityName)
	{
		if ( ! \class_exists($pageEntityName)) {
			throw new Exception("Class by name '$pageEntityName' has not been found, provided as page entity name");
		}
		$this->pageEntityName = $pageEntityName;
	}

	/**
	 * Execute controller
	 * @param RequestInterface $request
	 * @param ResponseInterface $response 
	 */
	public function execute(Request\RequestInterface $request, Response\ResponseInterface $response)
	{
		parent::execute($request, $response);

		$page = $this->getRequestPage();

		$templates = $this->getTemplates($page);

		/* @var $rootTemplate Template */
		$rootTemplate = $templates[0];

		/* @var $layout Layout */
		$layout = $rootTemplate->getLayout();
		if (empty($layout)) {
			throw new Exception("No layout defined for template #{$rootTemplate->getId()}");
		}

		$layoutPlaceHolderNames = array();

		/* @var $layoutPlaceHolders PersistentCollection */
		$layoutPlaceHolders = $layout->getPlaceHolders();

		/* @var $layoutPlaceHolder LayoutPlaceHolder */
		foreach ($layoutPlaceHolders as $layoutPlaceHolder) {
			$layoutPlaceHolderNames[] = $layoutPlaceHolder->getName();
		}

		/* @var $templateIds int[] */
		$templateIds = array();
		
		/* @var $template Template */
		foreach ($templates as $template) {
			$templateIds[] = $template->getId();
		}

		$em = $this->getDoctrineEntityManager();
		
		//TODO: parametrize
		$qb = $em->createQueryBuilder();
		$qb->select('tph')
			->from('Supra\\Controller\\Pages\\TemplatePlaceHolder', 'tph')
			->where(
				$qb->expr()->in('tph.layoutPlaceHolderName', $layoutPlaceHolderNames)
			)
			->where(
				$qb->expr()->in('tph.template.id', $templateIds)
			);

		$dql = $qb->getDQL();
		
		$query = $em->createQuery($dql);
		$result = $query->getResult();

		\Log::debug($result);

	}

	/**
	 * Generate response object
	 * @param Request\RequestInterface
	 * @return Response\Http
	 */
	public function getResponseObject(Request\RequestInterface $request)
	{
		return new Response\Http();
	}

	/**
	 * Get request page by current action
	 * @return Page
	 * @throws Exception
	 */
	protected function getRequestPage()
	{
		$action = $this->request->getActionString();
		$action = trim($action, '/');

		$em = $this->getDoctrineEntityManager();
		$er = $em->getRepository($this->getPageEntityName());

		//TODO: think about "enable path params" feature
		/* @var $page Page */
		$page = $er->findOneByPath($action);

		if (empty($page)) {
			//TODO: 404 page
			throw new Exception("No page found by path '$action' in pages controller");
		}

		return $page;
	}

	/**
	 * Get template list
	 * @param Page $page
	 * @return Template[]
	 * @throws Exception
	 */
	protected function getTemplates(Page $page)
	{
		/* @var $template Template */
		$template = $page->getTemplate();

		if (empty($template)) {
			//TODO: 404 page or specific error?
			throw new Exception("No template assigned to the page {$page->getId()}");
		}

		/* @var $templates Template[] */
		$templates = array();
		/* @var $rootTemplate Template */
		$rootTemplate = null;
		do {
			array_unshift($templates, $template);
			$rootTemplate = $template;
			$template = $template->getParent();
		} while ( ! is_null($template));

		return $templates;
	}
}