<?php

namespace Supra\Cms\BlogManager\Blog;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Application\PageApplicationCollection;

use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\Entity\ApplicationLocalization;
use Supra\Controller\Pages\Blog\BlogApplication;
use Supra\Controller\Pages\Finder;
use Supra\Cms\Exception\CmsException;

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
		
		$paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
		$total = $paginator->count();
		
		$result = $query->getResult();
		$responseData = array(
			'offset' => $offset,
			'total' => $total,
			'results' => array(),
		);
		
		$localizations = array();
		
		foreach ($result as $postPage) {
			$localizations[] = $postPage->getLocalization($localeId);
		}
		
		if ( ! empty($localizations)) {
		
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
					'page_id' => $postPage->getId(),
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
		
		$blogApp = $this->getBlogApplication();
		
		$templateId = $blogApp->getPostDefaultTemplateId();
		if (empty($templateId)) {
			throw new CmsException(null, "Please specify the new post template inside Blog Application settings");
		}
		
		$template = $this->entityManager->find(Entity\Template::CN(), $templateId);
		if ( ! empty($templateId)) {
			$templateLocalization = $template->getLocalization($localeId);
		}
		
		if (empty($templateLocalization)) {
			throw new CmsException(null, "Post template is missing, please configure it inside Blog Application settings");
		}
		
		$blogPostLocalization = new Entity\Blog\BlogApplicationPostLocalization($blogApp);
		$blogPostLocalization->setPageLocalization($pageData);
		
		$supraUser = $this->getUser();
		
		$blogUser = $blogApp->findUserBySupraUserId($supraUser->getId());
		$blogPostLocalization->setAuthor($blogUser);
		
		$this->entityManager->persist($blogPostLocalization);

		$this->entityManager->persist($page);
		
		$pageData->setIncludedInSearch($templateLocalization->isIncludedInSearch());
		$pageData->setVisibleInMenu($templateLocalization->isVisibleInMenu());
		$pageData->setVisibleInSitemap($templateLocalization->isVisibleInSitemap());
		
		$pageData->setTemplate($template);
		
		// @FIXME: hardcoded and not nice
		$pageData->setParentPageApplicationId('blog');
		
        $request = $this->getRequest();
        /* @var $request \Supra\Request\HttpRequest */
        $title = $request->getPostValue('title');
        $path = $request->getPostValue('path');

        $title = $title ? $title : 'New post';
        $path = $path ? $path : time();

        $pageData->setTitle($title);
        $pageData->setPathPart($path);
		
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