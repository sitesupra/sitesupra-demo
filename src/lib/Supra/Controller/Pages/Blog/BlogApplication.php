<?php

namespace Supra\Controller\Pages\Blog;

use Supra\Controller\Pages\Application\PageApplicationInterface;
use Supra\Controller\Pages\Entity;
use Supra\Uri\Path;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Supra\User\Entity\User;
use Supra\ObjectRepository\ObjectRepository;

use Supra\Controller\Pages\Entity\Blog\BlogApplicationUser;
use Supra\Database\Doctrine\Type\UtcDateTimeType;

/**
 * Blog application
 */
class BlogApplication implements PageApplicationInterface
{
	const PARAMETER_POST_TEMPLATE_ID = 'post_template_id';
	const PARAMETER_COMMENT_MODERATION_ENABLED = 'comment_moderation_enabled';
			
	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var Entity\ApplicationLocalization
	 */
	protected $applicationLocalization;
	
	/**
	 * @var array
	 */
	protected $blogApplicationUsers;
	

	/**
	 * {@inheritdoc}
	 * @param EntityManager $em
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
	}

	/**
	 * @return \Doctrine\ORM\EntityManager
	 */
	public function getEntityManager()
	{
		return $this->em;
	}
	
	/**
	 * {@inheritdoc}
	 * @param Entity\ApplicationLocalization $localization
	 */
	public function setApplicationLocalization(Entity\ApplicationLocalization $applicationLocalization)
	{
		$this->applicationLocalization = $applicationLocalization;
	}
	
	/**
	 * @return Entity\ApplicationLocalization
	 */
	public function getApplicationLocalization()
	{
		return $this->applicationLocalization;
	}
	
	/**
	 * {@inheritdoc}
	 * @param Entity\PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(Entity\PageLocalization $pageLocalization)
	{
		return new Path('');
	}

	/**
	 * {@inheritdoc}
	 * @param QueryBuilder $queryBuilder
	 * @param string $filter
	 * @return array
	 */
	public function getFilterFolders(QueryBuilder $queryBuilder, $filterName)
	{
		$folders = null;

		if ($filterName == 'list') {
			$folders = array();
		} else if ($filterName == '') {
			$folders = $this->getDefaultFilterFolders();
		} else {
			throw new \RuntimeException("Filter $filterName is not recognized");
		}

		return $folders;
		
	}

	/**
	 * @return array
	 */
	protected function getDefaultFilterFolders()
	{
		$listGroup = new Entity\TemporaryGroupPage();
		$listGroup->setTitle('list');
		$listGroup->setId($this->applicationLocalization->getId() . '_' . 'list');

		return array($listGroup);
	}

	/**
	 * {@inheritdoc}
	 * @param QueryBuilder $queryBuilder
	 * @param string $filterName
	 */
	public function applyFilters(QueryBuilder $queryBuilder, $filterName)
	{
		$filterName = (string) $filterName;
		
		if ($filterName == 'list') {
			$this->applyListFilter($queryBuilder);

		} else if ($filterName == '') {
			$this->applyDefaultFilter($queryBuilder);
			
		} else {
			throw new \RuntimeException("Filter $filterName is not recognized");
		}
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $queryBuilder
	 */
	protected function applyDefaultFilter(QueryBuilder $queryBuilder)
	{
		$queryBuilder->andWhere('e INSTANCE OF ' . Entity\GroupPage::CN());
	}

	/**
	 * @param \Doctrine\ORM\QueryBuilder $queryBuilder
	 */
	protected function applyListFilter(QueryBuilder $queryBuilder)
	{
		$queryBuilder
				->addSelect('COALESCE(l_.creationTime, l.creationTime) as HIDDEN ct')
				->andWhere('l INSTANCE OF ' . Entity\PageLocalization::CN())
				->orderBy('ct', 'DESC');
	}
	
	/**
	 * @return string
	 */
	public function getNewPostTemplate()
	{
		return $this->applicationLocalization->getParameterValue(self::PARAMETER_POST_TEMPLATE_ID);
	}
	
	public function setNewPostTemplate($templateLocalization)
	{
		$parameter = $this->applicationLocalization->getOrCreateParameter(self::PARAMETER_POST_TEMPLATE_ID);
		$parameter->setValue($templateLocalization->getId());
		
		$this->em->persist($parameter);
	}
	
	/**
	 * @FIXME: optimize? create PageApplicationUser? 
	 */
	public function getAllBlogApplicationUsers()
	{
		if ($this->blogApplicationUsers === null) {
			$userProvider = \Supra\ObjectRepository\ObjectRepository::getUserProvider($this);
			$users = $userProvider->findAllUsers();

			$userIds = Entity\Abstraction\Entity::collectIds($users);

			$blogUserCn = BlogApplicationUser::CN();
			$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($this);
			$blogUsers = $em->createQuery("SELECT bu FROM {$blogUserCn} bu WHERE bu.id IN (:ids)")
					->setParameter('ids', $userIds)
					->getArrayResult();

			$userMap = array();

			foreach ($blogUsers as $blogUser) {
				/* @var $blogUser BlogApplicationUser */
				$userMap[$blogUser->getSupraUserId()] = $blogUser;
			}

			$this->blogApplicationUsers = array();

			foreach ($users as $user) {
				/* @var $user \Supra\User\Entity\User */

				if ($this->doesUserHaveEditPermissions($user)) {

					if (isset($userMap[$user->getId()])) {
						$this->blogApplicationUsers[] = $userMap[$user->getId()];
					} else {
						$this->blogApplicationUsers[] = new BlogApplicationUser($user);
					}
				}
			}
		}

		return $this->blogApplicationUsers;
	}
	
	/**
	 * @FIXME: optimize?
	 * 
	 * Returns the tag list represented by 
	 * sorted by popularity array of tag name and usage count
	 */
	public function getAllTagsArray()
	{
		$pageFinder = new \Supra\Controller\Pages\Finder\PageFinder($this->em);
		
		$localizationFinder = new \Supra\Controller\Pages\Finder\LocalizationFinder($pageFinder);
		$localizationFinder->addFilterByParent($this->applicationLocalization, 1, 1);
		
		$result = $localizationFinder->getQueryBuilder()
				->select('l.id')
				->getQuery()
				->getScalarResult();
	
		$localizationIds = array();
		foreach ($result as $idRecord) {
			$localizationIds[] = $idRecord['id'];
		}

		$tagArray = array();
		
		if ( ! empty($localizationIds)) {
			
			$tagCn = Entity\LocalizationTag::CN();
			$tagArray = $this->em->createQuery("SELECT t.name AS name, count(t.id) as amount FROM {$tagCn} t WHERE t.localization IN (:ids) GROUP BY t.name ORDER BY amount DESC")
					->setParameter('ids', $localizationIds)
					->getScalarResult();
		} 
		
		return $tagArray;
	}
	
	/**
	 * @FIXME: pagination
	 * @FIXME: query builder
	 * 
	 * @param \Supra\Controller\Pages\Entity\PageLocalization $localization
	 * @param boolean $withUnapproved
	 * @return array
	 */
	public function getCommentsForLocalization(Entity\PageLocalization $localization, $withUnapproved = false)
	{
		$qb = $this->getCommentForLocalizationQueryBuilder($localization);
		
		if ( ! $withUnapproved) {
			$qb->andWhere('c.approved = 1');
		}
		
		$comments = $qb->getQuery()
				->getArrayResult();
		
		return $comments;
	}
	
	public function getCommentForApplicationQueryBuilder()
	{
		$qb = $this->em->createQueryBuilder();
		
		$qb->select('c')
				->from(Entity\Blog\BlogApplicationComment::CN(), 'c')
				->where('c.applicationLocalizationId = :applicationLocalizationId')
				->orderBy('c.creationTime', 'DESC')
				->setParameter('applicationLocalizationId', $this->applicationLocalization->getId());
		
		
		return $qb;
	}
	
	public function getCommentForLocalizationQueryBuilder(Entity\Abstraction\Localization $localization)
	{
		$qb = $this->getCommentForApplicationQueryBuilder();
		$qb->andWhere('c.pageLocalizationId = :localizationId')
				->setParameter('localizationId', $localization->getId());
		
		return $qb;
	}
	
	/**
	 * Check, if passed user have edit access rights for current blog application instance
	 * 
	 * @param \Supra\User\Entity\User $user
	 * @return boolean
	 */
	public function doesUserHaveEditPermissions(User $user)
	{
		$ap = ObjectRepository::getAuthorizationProvider('Supra\Cms\BlogManager');
		$appConfig = ObjectRepository::getApplicationConfiguration('Supra\Cms\BlogManager');
		
		if ($appConfig->authorizationAccessPolicy->isApplicationAllAccessGranted($user)) {
			return true;
		}

		if ($ap->isPermissionGranted($user, $this->applicationLocalization, Entity\Abstraction\Entity::PERMISSION_NAME_EDIT_PAGE)) {
			return true;
		}

		return false;
	}
	
	/**
	 * @param string $commentId
	 * @return Entity\Blog\BlogApplicationComment | null
	 */
	public function findCommentById($commentId)
	{
		$comment = $this->em->getRepository(Entity\Blog\BlogApplicationComment::CN())
				->findOneBy(array(
					'id' => $commentId, 
					'applicationLocalizationId' => $this->applicationLocalization->getId(),
		));
		
		return $comment;
	}

	/**
	 * Searches for BlogApplicationUser object by Supra User ID
	 * @param string $userId
	 * @return \Supra\Controller\Pages\Entity\Blog\BlogApplicationUser | null
	 */
	public function findUserBySupraUserId($userId)
	{
		$users = $this->getAllBlogApplicationUsers();
		
		foreach ($users as $user) {
			if ($user->getSupraUserId() == $userId) {
				return $user;
			}
		}
		
		return null;
	}
	
	/**
	 * @FIXME: this functionality must be implemented in some another way
	 * 
	 * @param array $localizationIds
	 */
	public function getAuthorsForLocalizationIds($localizationIds)
	{
		$postLocalizationCn = Entity\Blog\BlogApplicationPostLocalization::CN();
		
		$blogPostLocalizations = $this->em->createQuery("SELECT bpl FROM {$postLocalizationCn} bpl WHERE bpl.pageLocalizationId IN (:ids)")
				->setParameter('ids', $localizationIds)
				->getResult();
		
		$localizationAuthors = array();
		
		if ( ! empty($blogPostLocalizations)) {
					
			foreach ($blogPostLocalizations as $blogPostLocalization) {
				
				$localizationId = $blogPostLocalization->getPageLocalizationId();
				$supraUserId = $blogPostLocalization->getAuthorSupraUserId();
				
				$author = $this->findUserBySupraUserId($supraUserId);
				
				$localizationAuthors[$localizationId] = $author;
			}
		}
		
		return $localizationAuthors;
	}
	
	/**
	 * 
	 * @param \Supra\Controller\Pages\Blog\PageLocalization $localization
	 * @return \Supra\Controller\Pages\Entity\Blog\BlogApplicationUser | null
	 */
	public function findAuthorForLocalization(PageLocalization $localization)
	{
		$blogPostLocalization = $this->em->getRepository(Entity\Blog\BlogApplicationPostLocalization::CN())
				->findOneBy(array('pageLocalizationId' => $localization->getId()));
		
		if ( ! empty($blogPostLocalization)) {
			$supraUserId = $blogPostLocalization->getAuthorSupraUserId();
			return $this->findUserBySupraUserId($supraUserId);
		}
		
		return null;
	}
	
	/**
	 * @param array $localizationIds
	 */
	public function getCommentDataForLocalizationIds($localizationIds)
	{
		$qb = $this->getCommentForApplicationQueryBuilder();
		
		$qb->select('c.pageLocalizationId as localization, COUNT(c.id) as total, MIN(c.approved) as approved, MAX(c.creationTime) as latestCreationTime')
				->andWhere('c.pageLocalizationId IN (:ids)')
				->groupBy('c.pageLocalizationId')
				->setParameter('ids', $localizationIds);
		
		$result = $qb->getQuery()
				->getScalarResult();
		
		$commentsData = array();
		
		$today = strtotime('Today');
		
		foreach ($result as $resultRow) {
			
			$localizationId = $resultRow['localization'];
			$creationTime = UtcDateTimeType::staticConvertToPHPValue($resultRow['latestCreationTime']);
			
			$commentsData[$localizationId] = array(
				'total' => $resultRow['total'],
				'has_unapproved' => $resultRow['approved'],
				'has_new' => $creationTime->getTimestamp() > $today,
			);
		}
		
		return $commentsData;
	}
	
	/**
	 * @return \Supra\Controller\Pages\Entity\Blog\BlogApplicationComment
	 */
	public function createComment()
	{
		$blogComment = new Entity\Blog\BlogApplicationComment($this);
		$blogComment->setApproved($this->isCommentModerationEnabled() ? false : true );
		
		return $blogComment;
	}
	
	/**
	 * @TODO: do we need this? 
	 * 
	 * @param \Supra\Controller\Pages\Entity\Blog\BlogApplicationComment $comment
	 */
	public function storeComment(Entity\Blog\BlogApplicationComment $comment)
	{
		$this->em->persist($comment);
		$this->em->flush();
	}
	
	/**
	 * @return string
	 */
	public function isCommentModerationEnabled()
	{
		$value = $this->applicationLocalization
				->getParameterValue(self::PARAMETER_COMMENT_MODERATION_ENABLED, false);
		
		$boolType = new \Supra\Validator\Type\BooleanType();
		$boolType->validate($value);
		
		return $value;
	}
	
	/**
	 * @param boolean $enabled
	 */
	public function setCommentModerationEnabled($enabled)
	{
		$parameter = $this->applicationLocalization->getOrCreateParameter(self::PARAMETER_COMMENT_MODERATION_ENABLED);
		$parameter->setValue($enabled);
		
		$this->em->persist($parameter);
		$this->em->flush($parameter);
	}
}
