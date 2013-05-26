<?php

namespace Supra\Cms\BlogManager\Blog;

use Supra\Cms\CmsAction;
use Supra\Request;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Event\PostPrepareContentEventArgs;
use Supra\Controller\Pages\Application\PageApplicationCollection;

use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Blog\BlogApplication;
use Supra\Controller\Pages\Finder;

class BlogAction extends \Supra\Cms\ContentManager\PageManagerAction
{
	/**
	 * @var \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected $application;
	
	/**
	 * @var \Supra\Controller\Pages\Entity\ApplicationLocalization
	 */
	protected $applicationLocalization;
	

	/**
	 * Returns blog posts array
	 */
	public function postsAction()
	{
		$input = $this->getRequestInput();
		$localeId = $this->getLocale()
				->getId();
				
		$blogApp = $this->getBlogApplication();
		$blogAppLocalization = $this->getBlogApplicationLocalization();
		
		$em = ObjectRepository::getEntityManager($this);
		
		$pageFinder = new Finder\PageFinder($em);
		$pageFinder->addFilterByParent($blogAppLocalization->getMaster(), 1, 1);
		
		$qb = $pageFinder->getQueryBuilder();
		
		$qb->leftJoin('e.localizations', 'l_', 'WITH', 'l_.locale = :locale')
					->setParameter('locale', $localeId)
					->leftJoin('e.localizations', 'l')
					->andWhere('l_.id IS NOT NULL OR e.global = true OR (e.level = 0 AND e.global = false)');
		
		$blogApp->applyFilters($qb, 'list');
				
		$offset = $input->getValidIfExists('offset', 'smallint');
		$limit = $input->getValidIfExists('resultsPerRequest', 'smallint');
		
		$query = $qb->getQuery();
		$query->setFirstResult($offset);
		$query->setMaxResults($limit);
		
		$result = $query->getResult();
		$responseData = array(
			'offset' => $offset,
			'total' => 200,
			'results' => array(),
		);
		
		$localizations = array();
		
		foreach ($result as $postPage) {
			$localizations[] = $postPage->getLocalization($localeId);
		}
		
		$ids = Entity\Abstraction\Entity::collectIds($localizations);
		
		$localizationAuthors = $blogApp->getAuthorsForLocalizationIds($ids);
		$localizationCommentsData = $blogApp->getCommentDataForLocalizationIds($ids);
		
		
		$publicEm = ObjectRepository::getEntityManager('#public');
		$localizationCn = Entity\Abstraction\Localization::CN();
		$revisionResult = $publicEm->createQuery("SELECT l.id, l.revision FROM {$localizationCn} l WHERE l.id in (:ids)")
					->setParameter('ids', $ids)
					->getScalarResult();
		
		$publicRevisionMap = array();
		foreach ($revisionResult as $resultRow) {
			$publicRevisionMap[$resultRow['id']] = $resultRow['revision'];
		}
		
		
		/* @var $localizations \Supra\Controller\Pages\Entity\PageLocalization[] */
		foreach ($localizations as $localization) {
			
			$id = $localization->getId();
			
			$responseData['results'][] = array(
				'id' => $localization->getId(),
				'time' => $localization->getCreationTime()->format("Y-m-d H:i:s"),
				'title' => $localization->getTitle(),
				'author' =>	isset($localizationAuthors[$id]) ? $localizationAuthors[$id]->getName() : null,
				'comments' => isset($localizationCommentsData[$id]) ? $localizationCommentsData[$id] : array('total' => 0, 'has_new' => false, 'has_unapproved' => false),
				
				'published' => isset($publicRevisionMap[$id]) && $publicRevisionMap[$id] == $localization->getRevisionId() ? true : false,
				
				// @FIXME
				'localized' => true,
				
				'scheduled' => ($localization->getScheduleTime() instanceof \DateTime),
			);
		}
		
		$this->getResponse()
				->setResponseData($responseData);
	}
		
	public function createAction()
	{
		$this->isPostRequest();
		$this->lock();
		
		$parent = $this->getPageByRequestKey('parent_id');

		$this->checkActionPermission($parent, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE);

//		$eventManager = $this->entityManager->getEventManager();
//		$eventManager->dispatchEvent(AuditEvents::pagePreCreateEvent);
		
		$localeId = $this->getLocale()->getId();
		
		$page = new Entity\Page();
		$pageData = Entity\Abstraction\Localization::factory($page, $localeId);

		$this->entityManager->persist($page);
		
		// @FIXME: load template from "Blog settings"
		$parentLocalization = $parent->getLocalization($localeId);
		$template = $parentLocalization->getTemplate();
		$templateLocalization = $template->getLocalization($localeId);
		
		if (empty($template)) {
			throw new CmsException(null, "Template not specified or found");
		}
		
		$pageData->setIncludedInSearch($templateLocalization->isIncludedInSearch());
		$pageData->setVisibleInMenu($templateLocalization->isVisibleInMenu());
		$pageData->setVisibleInSitemap($templateLocalization->isVisibleInSitemap());
		
		$pageData->setTemplate($template);
		
		// @FIXME: hardcoded and not nice
		$pageData->setParentPageApplicationId('blog');
		
		// if isset title -> setTitle()
		
		// if isset pathPart -> setPathPart()
		$pathPart = time();
		$pageData->setPathPart($pathPart);	
		$pageData->setTitle('New post');
		
		try {
			$page->moveAsLastChildOf($parent);	
		} catch (DuplicatePagePathException $e) {
			
			throw new CmsException(null, $e->getMessage(), $e);
		
		} catch (\Exception $e) {
			if ($this->entityManager->isOpen()) {
				$this->entityManager->remove($page);
				$this->entityManager->remove($pageData);
				$this->entityManager->flush();
			}
			throw $e;
		}

		$this->entityManager->flush();
		$this->unlock();

		$this->writeAuditLog('%item% created', $pageData);

		
		$request = \Supra\Controller\Pages\Request\PageRequestEdit::factory($pageData);
		$request->setDoctrineEntityManager($this->entityManager);
		$request->getPlaceHolderSet();
		$request->createMissingPlaceHolders();
		$request->createMissingBlockProperties();
		
		$this->outputPage($pageData);
	}
	
	/**
	 *
	 */
	public function settingsAction()
	{
		$blogApplication = $this->getBlogApplication();
		
		$responseData = array(
			'authors' => array(),
			'tags' => array(),
		);
		
		$users = $blogApplication->getAllBlogApplicationUsers();
		/* @var $users \Supra\Controller\Pages\Entity\Blog\BlogApplicationUser[] */

		foreach ($users as $user) {
			$responseData['authors'][] = array(
				'id' => $user->getSupraUserId(),
				'name' => $user->getName(),
				'avatar' => $user->getAvatar(),
				'about' => $user->getAboutText(),
			);
		}
		
		$responseData['tags'] = $blogApplication->getAllTagsArray();
			
		$this->getResponse()
				->setResponseData($responseData);
	}
	
	/**
	 * Stores Blog application settings
	 * 
	 * @FIXME
	 */
	public function saveSettingsAction()
	{
		$this->isPostRequest();
		$input = $this->getRequestInput();
		
		if ($input->hasChild('author')) {
			
			$application = $this->getBlogApplication();
			
			$aboutAuthor = $input->getChild('author')
					->get('about');
			
			$application->setAuthorDescription($aboutAuthor);
			
			$this->entityManager->flush();
		}
		
		$this->getResponse()
				->setResponseData(true);
	}
	
	/**
	 * @return \Supra\Controller\Pages\Entity\ApplicationLocalization
	 */
	protected function getBlogApplicationLocalization()
	{
		if ($this->applicationLocalization === null) {

			$localizationId = $this->getRequestParameter('parent_id');
			
			$localization = $this->entityManager->find(ApplicationLocalization::CN(), $localizationId);
	
			if ( ! $localization instanceof ApplicationLocalization) {
				throw new \RuntimeException("ApplicationLocalization for id {$localizationId} not found");
			}
			
			$application = PageApplicationCollection::getInstance()
					->createApplication($localization, $this->entityManager);
				
			if ( ! $application instanceof BlogApplication) {
				throw new \RuntimeException('Specified localization does not belongs to BlogApplication');
			}
			
			$this->applicationLocalization = $localization;
		}
		
		return $this->applicationLocalization;
	}
	
	/**
	 * @return \Supra\Controller\Pages\Blog\BlogApplication
	 */
	protected function getBlogApplication()
	{
		if ($this->application === null) {
			$applicationLocalization = $this->getBlogApplicationLocalization();
			$this->application = PageApplicationCollection::getInstance()
					->createApplication($applicationLocalization, $this->entityManager);
		}
		
		return $this->application;
	}
}