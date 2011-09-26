<?php

namespace Supra\Cms\ContentManager;

use Supra\Controller\Pages\Entity;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Repository\PageRepository;
use Supra\Http\Cookie;
use Supra\Cms\CmsAction;
use Supra\NestedSet\Node\DoctrineNode;
use Doctrine\ORM\Query;
use Supra\Database\Doctrine\Hydrator\ColumnHydrator;
use Supra\Controller\Pages\Entity\ReferencedElement;
use Supra\FileStorage\Entity\Image;
use Supra\FileStorage\Entity\File;
use Supra\Cms\Exception\CmsException;

/**
 * Controller containing common methods
 */
abstract class PageManagerAction extends CmsAction
{
	const INITIAL_PAGE_ID_COOKIE = 'cms_content_manager_initial_page_id';

	/**
	 * @var EntityManager
	 */
	protected $entityManager;
	
	/**
	 * @var Entity\Abstraction\Data
	 */
	protected $pageData;

	/**
	 * Assign entity manager
	 */
	public function __construct()
	{
		parent::__construct();

		// Take entity manager of the page controller
//		$controller = $this->getPageController();
		// Will fetch connection for drafts
		$this->entityManager = ObjectRepository::getEntityManager($this);
	}

	/**
	 * TODO: must return configurable controller instance (use repository?)
	 * @return PageController
	 */
	protected function getPageController()
	{
		$controller = new \Project\Pages\PageController();

		// Override with the draft version connection
		$controller->setEntityManager($this->entityManager);
		
		return $controller;
	}

	/**
	 * @return PageRequestEdit
	 */
	protected function getPageRequest()
	{
		$controller = $this->getPageController();
		$localeId = $this->getLocale()->getId();
		$media = $this->getMedia();

		$request = new PageRequestEdit($localeId, $media);
		$response = $controller->createResponse($request);

		$controller->prepare($request, $response);

		$requestPageData = $this->getPageData();
		$request->setPageData($requestPageData);

		return $request;
	}

	/**
	 * TODO: hardcoded now
	 * @return string
	 */
	protected function getMedia()
	{
		return 'screen';
	}

	/**
	 * @return Entity\Abstraction\Data
	 * @throws ResourceNotFoundException
	 */
	protected function getPageData()
	{
		if (isset($this->pageData)) {
			return $this->pageData;
		}
		
		$pageId = $this->getRequestParameter('page_id');
		$localeId = $this->getLocale()->getId();

		if (empty($pageId)) {
			throw new ResourceNotFoundException("Page ID not provided");
		}
		
		$dataEntity = PageRequest::DATA_ENTITY;
		$dql = "SELECT d FROM $dataEntity d WHERE d.master = ?0 AND d.locale = ?1";
		$query = $this->entityManager->createQuery($dql);
		$query->execute(array($pageId, $localeId));

		try {
			$this->pageData = $query->getSingleResult();
			
			return $this->pageData;
		} catch (\Doctrine\ORM\NoResultException $notFound) {
			throw new ResourceNotFoundException("Page data for page {$pageId} locale {$localeId} not found", null, $notFound);
		}
	}

	/**
	 * Get first page ID to show in the CMS
	 * @return int
	 */
	protected function getInitialPageId()
	{
		$localeId = $this->getLocale()->getId();
		$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ABSTRACT_ENTITY);
		$page = null;

		// Try cookie
		if (isset($_COOKIE[self::INITIAL_PAGE_ID_COOKIE])) {
			$pageId = $_COOKIE[self::INITIAL_PAGE_ID_COOKIE];
			$page = $pageDao->findOneById($pageId);

			if ( ! empty($page)) {
				// Page localization must exist
				$pageData = $page->getData($localeId);

				if (empty($pageData)) {
					$page = null;
				}
			}
		}

		// Root page otherwise
		if (empty($page)) {
			$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ENTITY);
			/* @var $pageDao PageRepository */
			$pages = $pageDao->getRootNodes();
			if (isset($pages[0])) {
				$page = $pages[0];
			}
		}

		if (empty($page)) {
			return null;
		}

		$pageId = $page->getId();

		return $pageId;
	}

	/**
	 * Sets initial page ID to show in the CMS
	 * @param int $pageId
	 */
	protected function setInitialPageId($pageId)
	{
		$cookie = new Cookie(self::INITIAL_PAGE_ID_COOKIE, $pageId);
		$cookie->setExpire('+1 month');

		$this->getResponse()->setCookie($cookie);
	}

	/**
	 * 
	 * @param Entity\Abstraction\Data $pageData
	 */
	protected function outputPage(Entity\Abstraction\Data $pageData)
	{
		$data = null;

		if ($pageData instanceof Entity\TemplateData) {
			$data = $this->prepareTemplateData($pageData);
		}

		if ($pageData instanceof Entity\PageData) {
			$data = $this->preparePageData($pageData);
		}

		$this->getResponse()->setResponseData($data);
	}

	private function prepareTemplateData(Entity\TemplateData $templateData)
	{

		$template = $templateData->getTemplate();
		$parent = $template->getParent();
		$parentId = null;

		if ( ! is_null($parent)) {
			$parentId = $parent->getId();
		}

		$data = array(
			'id' => $template->getId(),
			'parent' => $parentId,
			//TODO: hardcoded
			'icon' => 'page',
			'preview' => '/cms/lib/supra/img/sitemap/preview/blank.jpg'
		);

		return $data;
	}

	private function preparePageData(Entity\PageData $pageData)
	{

		$page = $pageData->getPage();
		$template = $pageData->getTemplate();
		$parent = $page->getParent();
		$parentId = null;

		if ( ! is_null($parent)) {
			$parentId = $parent->getId();
		}

		$data = array(
			'id' => $page->getId(),
			'title' => $pageData->getTitle(),
			'template' => $template->getId(),
			'parent' => $parentId,
			'path' => $pageData->getPathPart(),
			//TODO: hardcoded
			'icon' => 'page',
			'preview' => '/cms/lib/supra/img/sitemap/preview/blank.jpg'
		);

		return $data;
	}
	
	/**
	 * Will publish page currently inside pageData property or found by page_id
	 * and locale query parameters
	 */
	protected function publish()
	{
		$controller = $this->getPageController();
		$publicEm = ObjectRepository::getEntityManager($controller);
		
		$pageRequest = $this->getPageRequest();
		
		$copyContent = function() use ($pageRequest, $publicEm) {
			$pageRequest->publish($publicEm);
		};
		
		$publicEm->transactional($copyContent);
	}
	
	/**
	 * Converts referenced element to JS array
	 * @param ReferencedElement\ReferencedElementAbstract $element
	 * @return array
	 */
	protected function convertReferencedElementToArray(ReferencedElement\ReferencedElementAbstract $element)
	{
		$data = $element->toArray();
		$localeId = $this->getLocale()->getId();
		$fs = ObjectRepository::getFileStorage($this);
		$em = $fs->getDoctrineEntityManager();
		
		if ($element instanceof ReferencedElement\LinkReferencedElement) {
			
			if ($element->getResource() == 'file') {

				$fileId = $element->getFileId();
				$file = $em->find(File::CN(), $fileId);

				if ($file instanceof File) {
					$fileInfo = $fs->getFileInfo($file, $localeId);
					$data['file_path'] = $fileInfo['path'];
				}
			}
		} elseif ($element instanceof ReferencedElement\ImageReferencedElement) {
			
			$imageId = $element->getImageId();
			$image = $em->find(Image::CN(), $imageId);

			if ($image instanceof Image) {
				$info = $fs->getFileInfo($image, $localeId);
				$data['image'] = $info;
			}
		}
		
		return $data;
	}
	
	protected function delete()
	{
		$this->isPostRequest();
	
		$pageId = $this->getRequestParameter('page_id');
		$draftEm = ObjectRepository::getEntityManager('Supra\Cms');
		$publicEm = ObjectRepository::getEntityManager('');
		$trashEm = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\Trash');
		
		$pageRequest = $this->getPageRequest();
		
		// If entity is a page, then get it template
		// and create they copies for _trash scheme
		$draftPage = $draftEm->find(PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
		if ($draftPage instanceof Entity\Page) {
			$draftPageCollection = $draftPage->getDataCollection();
			foreach ($draftPageCollection as $pageLocalization) {
				$draftTpl = $pageLocalization->getTemplate();
				$draftTplId = $draftTpl->getId();
				$tpl = $publicEm->find(PageRequest::TEMPLATE_ENTITY, $draftTplId);
				$trashEm->merge($tpl);
			}
			$draftEm->flush();
		}

		// We should delete published version also (if any)
		$publicEm = ObjectRepository::getEntityManager('');
		$publicPage = $publicEm->find(PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
		if ($publicPage instanceof Entity\Abstraction\Page) {
			$publicPageCollection = $publicPage->getDataCollection();
			foreach ($publicPageCollection as $pageLocalization) {
				$publicEm->remove($pageLocalization);
			}
			$placeholders = $publicPage->getPlaceHolders();
			foreach($placeholders as $placeholder){
				$publicEm->remove($placeholder);
			}
			$publicEm->flush();
		}
		
		$pageRequest->moveBetweenManagers($draftEm, $trashEm, $pageId);
		$this->getResponse()
				->setResponseData(true);
	}
	
	/**
	 * Will move all page data and page itself from trash into draft tables
	 */
	protected function restore()
	{
		$this->isPostRequest();
		
		$pageId = $this->getRequestParameter('page_id');
		$publicEm = ObjectRepository::getEntityManager('');
		$draftEm = ObjectRepository::getEntityManager('Supra\Cms');
		$trashEm = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\Trash');
		
		// Override Cms entity manager to handle trash pages
		$this->entityManager = $trashEm;
		$pageRequest = $this->getPageRequest();
		
		$trashPage = $trashEm->find(PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
		if ($trashPage instanceof Entity\Page) {

			$trashPageCollection = $trashPage->getDataCollection();
			foreach($trashPageCollection as $pageLocalization) {
				$templateId = $pageLocalization->getTemplate()->getId();
				
				$tpl = $publicEm->find(PageRequest::TEMPLATE_ENTITY, $templateId);
				if ( ! ($tpl instanceof Entity\Template)) {
					throw new \Supra\Controller\Pages\Exception\RuntimeException('It is impossible to restore page as it template was deleted');
				}
			}
			
			$page = $pageRequest->moveBetweenManagers($trashEm, $draftEm, $pageId);
		}
		else {
			$page = $pageRequest->moveBetweenManagers($trashEm, $draftEm, $pageId, true);
		}

		// Restore default entity manager
		$this->entityManager = ObjectRepository::getEntityManager($this);
		
		// TODO: move action
	}
	
	
	/**
	 * Checks, weither the page is locked by current user or not,
	 * will throw an exception if no, and update lock modified time if yes
	 * @throws CmsException if page is locked by another user
	 */
	protected function checkLock()
	{
		$this->isPostRequest();
		
		$userId = $this->getUser()->getId();
		$pageData = $this->getPageData();
		
		$pageLock = $pageData->getLock();
		
		if ($pageLock instanceof Entity\LockData) {
			if (($pageLock->getUser() != $userId)) {
				throw new CmsException('page.error.page_locked', 'Page is locked by another user');
			} else {
				$pageLock->setModifiedTime(new \DateTime('now'));
				$this->entityManager->flush();
			}
		} 
	}
	
	/**
	 * Removes page lock if exists
	 */
	protected function unlockPage()
	{
		$this->isPostRequest();
		
		$userId = $this->getUser()->getId();
		$pageData = $this->getPageData();
		
		$pageLock = $pageData->getLock();
		
		if ($pageLock instanceof Entity\LockData) {
			$this->entityManager->remove($pageLock);
			$pageData->setLock(null);

			$this->entityManager->flush();
		}
	}
	
	/**
	 * Sets page lock, if no lock is found, or if "force"-locking is used;
	 * will output current lock data if page locked by another user and 
	 * force action is not allowed or not provided
	 */
	protected function lockPage()
	{
		$this->isPostRequest();
		
		$userId = $this->getUser()->getId();
		$pageData = $this->getPageData();
		
		$allowForced = true; // TODO: hardcoded, should be based on current User rights/auth
		$force = (bool)$this->getRequestParameter('force');
		
		try {
			$this->checkLock();
		} catch (\Exception $e) {
			if ( ! $force ||  ! $allowForced) {
				
				$pageLock = $pageData->getLock();
				$lockedBy = $pageLock->getUser();
				
				$userProvider = \Supra\ObjectRepository\ObjectRepository::getUserProvider($this);
				$lockOwner = $userProvider->findUserById($lockedBy);
				
				if (!($lockOwner instanceof User)) {
					throw new \Supra\Controller\Pages\Exception\RuntimeException('Failed to load user-data for lock owner');
				}

				$response = array(
					'username' => $lockOwner->getName(),
					'datetime' => $pageLock->getCreatedTime()->format('c'),
					'allow_unlock' => $allowForced,
				);
				
				$this->getResponse()
						->setResponseData($response);
				return;
				
			}
		}
		
		$pageLock = new Entity\LockData;
		$this->entityManager->persist($pageLock);
		
		$pageLock->setUser($userId);
		$pageData->setLock($pageLock);
		$this->entityManager->flush();
	
		$this->getResponse()->setResponseData(true);
	}
	
}
