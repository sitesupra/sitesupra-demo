<?php

namespace Supra\Controller\Pages;

use Supra\Controller\ControllerAbstraction,
		Supra\Controller\Response,
		Supra\Controller\Request,
		Supra\Controller\Pages\Exception,
		Doctrine\ORM\PersistentCollection,
		Supra\Database\Doctrine,
		Supra\Locale\Data as LocaleData;

/**
 * Page controller
 */
class Controller extends ControllerAbstraction
{

	/**
	 * Page class to be used
	 * @var string
	 */
	const PAGE_ENTITY = 'Supra\\Controller\\Pages\\Page';

	/**
	 * Page data class to be used
	 * @var string
	 */
	const PAGE_DATA_ENTITY = 'Supra\\Controller\\Pages\\PageData';

	/**
	 * Current locale, set on execute start
	 * @var string
	 */
	protected $locale;

	/**
	 * Current media type
	 * @var string
	 */
	protected $media = 'screen';

	/**
	 * Construct
	 */
	public function  __construct()
	{
		$this->setLocale();
		$this->setMedia();
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	protected function getDoctrineEntityManager()
	{
		$em = Doctrine::getInstance()->getEntityManager();
		return $em;
	}

	/**
	 * Sets current locale
	 */
	protected function setLocale()
	{
		$this->locale = LocaleData::getInstance()->getCurrent();
	}

	/**
	 * Sets current media
	 */
	protected function setMedia()
	{
		$this->media = 'screen';
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
		\Log::debug('Found page #', $page->getId());

		$templates = $page->getTemplates();
		if (empty($templates[0])) {
			throw new Exception('Response from getTemplates should contain at least 1 template for page #' . $page->getId());
		}
		/* @var $rootTemplate Template */
		$rootTemplate = $templates[0];

		\Log::debug("Root template #{$rootTemplate->getId()} found for page #{$page->getId()}");

		/* @var $layout Layout */
		$layout = $rootTemplate->getLayout($this->media);
		if (empty($layout)) {
			throw new Exception("No layout defined for template #{$rootTemplate->getId()}");
		}
		\Log::debug("Root template {$rootTemplate->getId()} has layout {$layout->getFile()} for media {$this->media}");

		$layoutPlaceHolderNames = $layout->getPlaceHolderNames();

		\Log::debug('Layout place holder names: ', $layoutPlaceHolderNames);

		/* @var $templateIds int[] */
		$templateIds = array();
		
		/* @var $template Template */
		foreach ($templates as $template) {
			$templateIds[] = $template->getId();
		}

		\Log::debug('Found these templates: ', implode(', ', $templateIds));

		$em = $this->getDoctrineEntityManager();
		
		//TODO: parametrize
		$qb = $em->createQueryBuilder();
		$qb->select('tph')
			->from('Supra\Controller\Pages\TemplatePlaceHolder', 'tph')
			->where(
				$qb->expr()->in('tph.layoutPlaceHolderName', $layoutPlaceHolderNames)
			)
			->where(
				$qb->expr()->in('tph.template.id', $templateIds)
			);

		$dql = $qb->getDQL();
		
		$query = $em->createQuery($dql);
		$result = $query->getResult();

		\Log::debug(count($result));

		$response->output('So far so good');

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
		$er = $em->getRepository(static::PAGE_DATA_ENTITY);

		$searchCriteria = array(
			'locale' => $this->locale,
			'path' => $action,
		);

		//TODO: think about "enable path params" feature
		
		/* @var $page PageData */
		$pageData = $er->findOneBy($searchCriteria);

		if (empty($pageData)) {
			//TODO: 404 page
			throw new Exception("No page found by path '$action' in pages controller");
		}
		
		return $pageData->getPage();
	}

}