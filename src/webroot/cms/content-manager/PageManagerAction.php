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
use Supra\Cms\Exception\ObjectLockedException;
use Supra\User\Entity\User;
use Supra\Cms\Exception\CmsException;
use Supra\Uri\Path;
use Supra\Controller\Pages\Application\PageApplicationCollection;

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
	 * @var Entity\Abstraction\Localization
	 */
	protected $pageData;
	
	/**
	 * @var PageController
	 */
	private $pageController;

	/**
	 * Assign entity manager
	 */
	public function __construct()
	{
		parent::__construct();

		// Will fetch connection for drafts
		$this->entityManager = ObjectRepository::getEntityManager($this);
	}

	/**
	 * TODO: must return configurable controller instance (use repository?)
	 * @return PageController
	 */
	protected function getPageController()
	{
		if (is_null($this->pageController)) {
			$this->pageController = new \Project\Pages\PageController();

			// Override with the draft version connection
			$this->pageController->setEntityManager($this->entityManager);
		}
		
		return $this->pageController;
	}

	/**
	 * @return PageRequestEdit
	 */
	protected function getPageRequest()
	{
		$controller = $this->getPageController();
		$media = $this->getMedia();
		$user = $this->getUser();
		$requestPageLocalization = $this->getPageLocalization();

		$request = PageRequestEdit::factory($requestPageLocalization, $media);
		$response = $controller->createResponse($request);

		$controller->prepare($request, $response);

		$request->setUser($user);

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
	 *
	 * @return Entity\Abstraction\AbstractPage
	 */
	protected function getPage()
	{
		$page = null;
		
		if (isset($this->pageData)) {
			$page = $this->pageData->getMaster();
		} else {
			$page = $this->getPageByRequestKey('page_id');
		}
		
		return $page;
	}

	/**
	 * @return Entity\Abstraction\Localization
	 * @throws ResourceNotFoundException
	 */
	protected function getPageLocalization()
	{
		// FIXME: using requested locale would make more common sense with global/local switch
		if (isset($this->pageData)) {
			return $this->pageData;
		}
		
		$this->pageData = $this->getPageLocalizationByRequestKey('page_id');

		if (empty($this->pageData)) {
			$pageId = $this->getRequestParameter('page_id');
			throw new CmsException('sitemap.error.page_not_found', "Page data for page {$pageId} not found");
		}
		
		return $this->pageData;
	}
	
	protected function getPageByRequestKey($key)
	{
		$data = $this->getPageLocalizationByRequestKey($key);

		if (empty($data)) {
			return null;
		}
		
		$page = $data->getMaster();
		
		return $page;
	}
	
	protected function getPageLocalizationByRequestKey($key)
	{
		$pageId = $this->getRequestParameter($key);

		if (empty($pageId)) {
			return null;
		}
		
		$data = $this->entityManager->find(Entity\Abstraction\Localization::CN(), $pageId);

		return $data;
	}

	/**
	 * Get first page localization ID to show in the CMS
	 * @return Entity\Abstraction\Localization
	 */
	protected function getInitialPageLocalization()
	{
		$localeId = $this->getLocale()->getId();
		$localization = null;

		// Try cookie
		if (isset($_COOKIE[self::INITIAL_PAGE_ID_COOKIE])) {
			$pageLocalizationId = $_COOKIE[self::INITIAL_PAGE_ID_COOKIE];
			$localization = $this->entityManager->find(Entity\Abstraction\Localization::CN(), $pageLocalizationId);
		}

		// Root page otherwise
		if (empty($localization)) {
			$page = null;
			$pageDao = $this->entityManager->getRepository(PageRequest::PAGE_ENTITY);
			/* @var $pageDao PageRepository */
			$pages = $pageDao->getRootNodes();
			
			if (isset($pages[0])) {
				$page = $pages[0];
			}
			
			if ($page instanceof Entity\Abstraction\AbstractPage) {
				$localization = $page->getLocalization($localeId);
			}
		}

		if (empty($localization)) {
			return null;
		}
		
		return $localization;
	}

	/**
	 * Sets initial page ID to show in the CMS
	 * @param string $pageId
	 */
	protected function setInitialPageId($pageId)
	{
		$cookie = new Cookie(self::INITIAL_PAGE_ID_COOKIE, $pageId);
		$cookie->setExpire('+1 month');
		$_COOKIE[self::INITIAL_PAGE_ID_COOKIE] = $pageId;

		$this->getResponse()->setCookie($cookie);
	}

	/**
	 * 
	 * @param Entity\Abstraction\Localization $pageData
	 */
	protected function outputPage(Entity\Abstraction\Localization $pageData)
	{
		$data = null;

		$data = $this->loadNodeMainData($pageData);
		
		// Add missing parent page data ID
		$parentData = $pageData->getParent();
		$parentDataId = null;
		
		if ( ! is_null($parentData)) {
			$parentDataId = $parentData->getId();
		}
		
		$data['parent'] = $parentDataId;
		
		$this->getResponse()->setResponseData($data);
	}

	/**
	 * Loads main node data array
	 * @param Entity\Abstraction\Localization $data
	 * @return array
	 */
	protected function loadNodeMainData(Entity\Abstraction\Localization $data)
	{
		$page = $data->getMaster();
		$locale = $data->getLocale();
		
		// Main data
		$array = array(
			'id' => $data->getId(),
			'title' => $data->getTitle(),
			
			// TODO: hardcoded
			'icon' => $data instanceof Entity\GroupLocalization ? 'group' : ($page->getLevel() === 0 ? 'home' : 'page'),
			'preview' => '/cms/lib/supra/img/sitemap/preview/' . ($data instanceof Entity\GroupLocalization ? 'group' : 'blank') . '.jpg',
		);
		
		// Template ID
		if ($data instanceof Entity\PageLocalization) {
			$templateId = $data->getTemplate()
					->getId();
			
			$array['template'] = $templateId;
		}
		
		// Node type
		$type = Entity\Abstraction\Entity::PAGE_DISCR;
		if ($data instanceof Entity\GroupLocalization) {
			$type = Entity\Abstraction\Entity::GROUP_DISCR;
		} elseif ($page instanceof Entity\ApplicationPage) {
			$type = Entity\Abstraction\Entity::APPLICATION_DISCR;
			$array['application_id'] = $page->getApplicationId();
		}
		$array['type'] = $type;
		
		// Path data
		$pathPart = null;
		$applicationBasePath = new Path('');
		
		if ($data instanceof Entity\PageLocalization) {
			$pathPart = $data->getPathPart();
			
			if ( ! $page->isRoot()) {
				$parentPage = $page->getParent();
				$parentLocalization = $parentPage->getLocalization($locale);
				
				if (is_null($parentLocalization)) {
					throw new CmsException(null, "Parent page has no localization in the selected language");
				}
				
				if ($parentPage instanceof Entity\ApplicationPage) {
					$applicationId = $parentPage->getApplicationId();
					$application = PageApplicationCollection::getInstance()
							->createApplication($parentLocalization, $this->entityManager);
					
					$application->showInactivePages(true);
					
					if (empty($application)) {
						throw new CmsException(null, "Application '$applicationId' was not found");
					}
					
					$applicationBasePath = $application->generatePath($data);
				}
			}
		}
		
		// TODO: maybe should send "null" when path is not allowed? Must fix JS then
		$array['path'] = $pathPart;
		// Additional base path received from application
		$array['basePath'] = $applicationBasePath->getFullPath(Path::FORMAT_RIGHT_DELIMITER);
		
		return $array;
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
	
		$draftPage = $this->getPageLocalization()->getMaster();
		$pageId = $draftPage->getId();
		$draftEm = ObjectRepository::getEntityManager('Supra\Cms');
		$publicEm = ObjectRepository::getEntityManager('');
		$trashEm = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\Trash');
		
		$pageRequest = $this->getPageRequest();
		
		// If entity is a page, then get it template
		// and create they copies for _trash scheme
		$draftPage = $draftEm->find(PageRequest::PAGE_ABSTRACT_ENTITY, $pageId);
		
		if ($draftPage instanceof Entity\Page) {
			$draftPageCollection = $draftPage->getLocalizations();
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
		if ($publicPage instanceof Entity\Abstraction\AbstractPage) {
			$publicPageCollection = $publicPage->getLocalizations();
			foreach ($publicPageCollection as $pageLocalization) {
				$publicEm->remove($pageLocalization);
			}
			$placeholders = $publicPage->getPlaceHolders();
			foreach($placeholders as $placeholder){
				$publicEm->remove($placeholder);
			}
			$publicEm->flush();
		}
		
		$pageLocalizationId = $this->getPageLocalization()->getId();
		$pageRequest->moveBetweenManagers($draftEm, $trashEm, $pageLocalizationId);
		$this->getResponse()
				->setResponseData(true);
	}
	
	/**
	 * Will move all page data and page itself from trash into draft tables
	 */
	protected function restore()
	{
		$this->isPostRequest();
		
		//$pageDataId = $this->getRequestParameter('page_id');
		$pageDataId = $this->getPageLocalization()->getId();
		$publicEm = ObjectRepository::getEntityManager('');
		$draftEm = ObjectRepository::getEntityManager('Supra\Cms');
		$trashEm = ObjectRepository::getEntityManager('Supra\Cms\Abstraction\Trash');
		
		// Override Cms entity manager to handle trash pages
		$this->entityManager = $trashEm;
		$pageRequest = $this->getPageRequest();
		
		$trashPageLocalization = $trashEm->find(Entity\Abstraction\Localization::CN(), $pageDataId);
		
		if ( ! $trashPageLocalization instanceof Entity\Abstraction\Localization) {
			throw new CmsException(null, "Page wasn't found in the recycle bin anymore");
		}
		
		$trashPage = $trashPageLocalization->getMaster();
		
		if ($trashPage instanceof Entity\Page) {

			$trashPageCollection = $trashPage->getLocalizations();
			foreach($trashPageCollection as $pageLocalization) {
				$templateId = $pageLocalization->getTemplate()->getId();
				
				$tpl = $publicEm->find(Entity\Template::CN(), $templateId);
				if ( ! ($tpl instanceof Entity\Template)) {
					throw new CmsException(null, 'It is impossible to restore page as its template was deleted');
				}
			}
			
			$page = $pageRequest->moveBetweenManagers($trashEm, $draftEm, $pageDataId);
		}
		else {
			$page = $pageRequest->moveBetweenManagers($trashEm, $draftEm, $pageDataId, true);
		}

		// Restore default entity manager
		$this->entityManager = ObjectRepository::getEntityManager($this);
		
		// TODO: move action
	}
	
	
	/**
	 * Checks, weither the page is locked by current user or not,
	 * will throw an exception if no, and update lock modified time if yes
	 * @throws ObjectLockedException if page is locked by another user
	 */
	protected function checkLock($createLockOnMiss = true)
	{
		$this->isPostRequest();
		
		$userId = $this->getUser()->getId();
		$pageData = $this->getPageLocalization();
		
		$pageLock = $pageData->getLock();
		
		if ($pageLock instanceof Entity\LockData) {
			if (($pageLock->getUserId() != $userId)) {
				throw new ObjectLockedException('page.error.page_locked', 'Page is locked by another user');
			} else {
				$pageLock->setModifiedTime(new \DateTime('now'));
				$this->entityManager->flush();
			}
		} elseif ($createLockOnMiss) {
			// Creates lock if doesn't exist
			$this->createLock($pageData, $userId);
		}
	}
	
	/**
	 * Removes page lock if exists
	 */
	protected function unlockPage()
	{
		$this->isPostRequest();
		
		$userId = $this->getUser()->getId();
		$pageData = $this->getPageLocalization();
		
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
		$pageData = $this->getPageLocalization();
		
		$allowForced = true; // TODO: hardcoded, should be based on current User rights/auth
		$force = (bool)$this->getRequestParameter('force');
		
		try {
			$this->checkLock();
		} catch (ObjectLockedException $e) {
			if ( ! $force ||  ! $allowForced) {
				
				$pageLock = $pageData->getLock();
				$lockedBy = $pageLock->getUserId();
				
				$userProvider = ObjectRepository::getUserProvider($this);
				$lockOwner = $userProvider->findUserById($lockedBy);
				
				// If not found will show use ID
				$userName = '#' . $lockedBy;
				
				if ($lockOwner instanceof User) {
					$userName = $lockOwner->getName();
				}

				$response = array(
					'username' => $userName,
					'datetime' => $pageLock->getCreatedTime()->format('c'),
					'allow_unlock' => $allowForced,
				);
				
				$this->getResponse()
						->setResponseData($response);
				return;
			}
		}
		
		$this->createLock($pageData, $userId);
	
		$this->getResponse()->setResponseData(true);
	}

	/**
	 * Creates the lock inside the database
	 * @param Entity\Abstraction\Localization $pageData
	 * @param string $userId
	 */
	protected function createLock(Entity\Abstraction\Localization $pageData, $userId)
	{
		$pageLock = new Entity\LockData();
		$this->entityManager->persist($pageLock);
		
		$pageLock->setUserId($userId);
		$pageData->setLock($pageLock);
		$this->entityManager->flush();
	}
	
}
