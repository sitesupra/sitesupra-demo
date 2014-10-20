<?php

namespace Supra\Package\Cms\Pages\Application;

use Doctrine\ORM\QueryBuilder;
use Supra\Package\Cms\Entity\PageLocalization;
use Supra\Package\Cms\Entity\Blog\BlogApplicationUser;
use Supra\Package\Cms\Uri\Path;


use Supra\Controller\Pages\Entity;
use Supra\User\Entity\User;
use Supra\ObjectRepository\ObjectRepository;

use Supra\Database\Doctrine\Type\UtcDateTimeType;

/**
 * Blog application
 */
class BlogPageApplication extends PageApplication
{
	const PARAMETER_POST_TEMPLATE_ID = 'post_template_id';
	const PARAMETER_COMMENT_MODERATION_ENABLED = 'comment_moderation_enabled';
    const POPULAR_TAG_LIMIT = 20;

	protected $id = 'blog';
	protected $title = 'Blog';
	protected $icon = '/public/cms/content-manager/sitemap/images/apps/forum.png';

	/**
	 * @var BlogApplicationUser[]
	 */
	protected $blogApplicationUsers;
	
	/**
	 * {@inheritdoc}
	 * @param PageLocalization $pageLocalization
	 * @return Path
	 */
	public function generatePath(PageLocalization $pageLocalization)
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

        switch ($filterName) {
            case 'list':
                return $folders;
                break;
            case 'group':
                return $folders;
                break;
            case 'byYear':
				$queryBuilder = clone($queryBuilder);

				$queryBuilder->select('l.creationYear AS year, l.creationMonth AS month, COUNT(l.id) AS childrenCount')
                        ->addSelect('l.creationTime as HIDDEN ct')
						->groupBy('year, month')
						->orderBy('ct', 'DESC');

				$months = $queryBuilder->getQuery()
						->getResult();

				foreach ($months as $monthData) {

					$year = $monthData['year'];
					$month = $monthData['month'];
					$numberChildren = $monthData['childrenCount'];

					if ($year <= 0 || $month <= 0) {
						$yearMonth = '0000-00';
						$yearMonthTitle = $yearMonth; //'Unknown';
					} else {
						$yearMonth = str_pad($year, 4, '0', STR_PAD_LEFT) . '-' . str_pad($month, 2, '0', STR_PAD_LEFT);
                        $yearMonthTitle = date("F", mktime(0, 0, 0, $month, 10));
                        $yearMonthTitle .= ' ' . $year;
					}

					$group = new Entity\TemporaryGroupPage();
					$group->setTitle($yearMonthTitle);
					$group->setNumberChildren($numberChildren);
					
					$groupDate = \DateTime::createFromFormat('U', mktime(0,0,0, $month, 1, $year));
					
					$group->setGroupDate($groupDate);

					$id = $this->applicationLocalization->getId()
							. '_' . $yearMonth;
					$group->setId($id);

					$folders[] = $group;

				}
                return $folders;
                break;
            case '':
                $folders = $this->getDefaultFilterFolders();
                return $folders;
                break;
            default:
                throw new \RuntimeException("Filter $filterName is not recognized");
                break;
        }		
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
			
        } else if ($filterName == 'group') {
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
			$blogUsers = $em->createQuery("SELECT bu FROM {$blogUserCn} bu WHERE bu.supraUserId IN (:ids)")
					->setParameter('ids', $userIds)
					->getResult();

			$userMap = array();

			foreach ($blogUsers as $blogUser) {
				/* @var $blogUser BlogApplicationUser */
				$supraUserId = $blogUser->getSupraUserId();
				$userMap[$supraUserId] = $blogUser;
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
        return $this->getTagArray();
	}
    
    
    public function getPopularTagsArray()
    {
        return $this->getTagArray(self::POPULAR_TAG_LIMIT);
    }
    
    
    private function getTagArray($limit = null)
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
            $query = $this->em->createQuery("SELECT t.name AS name, count(t.id) as total FROM {$tagCn} t WHERE t.localization IN (:ids) GROUP BY t.name ORDER BY total DESC")
					->setParameter('ids', $localizationIds);
					
            if ($limit) {
                $query->setMaxResults($limit);
            }
            
            $tagArray = $query->getScalarResult();
		} 
		
		return $tagArray;
    }
	
	public function deleteTagByName($name)
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
	
		if ( ! empty($localizationIds)) {
			
			$tagCn = Entity\LocalizationTag::CN();
			$this->em->createQuery("DELETE FROM {$tagCn} t WHERE t.localization IN (:ids) AND t.name = :name")
					->setParameter('ids', $localizationIds)
					->setParameter('name', $name)
					->execute();
			
			$this->em->flush();
			
			// Clear the public schema also
			// @FIXME: looks wrong
			// @FIXME: what about cache?
			$publicEm = ObjectRepository::getEntityManager('#public');
			$publicEm->createQuery("DELETE FROM {$tagCn} t WHERE t.localization IN (:ids) AND t.name = :name")
					->setParameter('ids', $localizationIds)
					->setParameter('name', $name)
					->execute();
		
			$publicEm->flush();
		}
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
//		$value = $this->applicationLocalization
//				->getParameterValue(self::PARAMETER_COMMENT_MODERATION_ENABLED, false);
		
		$value = $this->getParameterValue(self::PARAMETER_COMMENT_MODERATION_ENABLED, false);
		
		$boolType = new \Supra\Validator\Type\BooleanType();
		$boolType->validate($value);
		
		return $value;
	}
	
	/**
	 * @param boolean $enabled
	 */
	public function setCommentModerationEnabled($enabled)
	{
//		$parameter = $this->applicationLocalization->getOrCreateParameter(self::PARAMETER_COMMENT_MODERATION_ENABLED);
		$parameter = $this->getOrCreateParameter(self::PARAMETER_COMMENT_MODERATION_ENABLED);
		$parameter->setValue($enabled);
		
		$this->em->persist($parameter);
		$this->em->flush($parameter);
	}
	
	/**
	 * @param string $templateId
	 */
	public function setPostDefaultTemplateId($templateId)
	{
//		$parameter = $this->applicationLocalization->getOrCreateParameter(self::PARAMETER_POST_TEMPLATE_ID);
		$parameter = $this->getOrCreateParameter(self::PARAMETER_POST_TEMPLATE_ID);
		$parameter->setValue($templateId);
		
		$this->em->persist($parameter);
		$this->em->flush($parameter);
	}
	
	/**
	 * @return string
	 */
	public function getPostDefaultTemplateId()
	{
//		$value = $this->applicationLocalization
//				->getParameterValue(self::PARAMETER_POST_TEMPLATE_ID, null);
		
		$value = $this->getParameterValue(self::PARAMETER_POST_TEMPLATE_ID, null);

		return $value;
	}
	
	/**
	 * @return array
	 */
	protected function getApplicationLocalizationParameters()
	{
		$queryString = sprintf(
				'SELECT p FROM %s p WHERE p.localizationId = :id',
				Entity\ApplicationLocalizationParameter::CN()
		);
		
		return $em->createQuery($queryString)
					->setParameter('id', $this->applicationLocalization->getId())
					->getResult();
	}
	
	protected function findParameter($name)
	{
		$queryString = sprintf(
				'SELECT p FROM %s p WHERE p.localizationId = :id AND p.name = :name',
				Entity\ApplicationLocalizationParameter::CN()
		);

		return $this->entityManager->createQuery($queryString)
					->setParameter('id', $this->applicationLocalization->getId())
					->setParameter('name', $name)
					->getOneOrNullResult();
	}
	
	protected function getOrCreateParameter($name)
	{
		$parameter = $this->findParameter($name);
		
		if ($parameter === null) {
			$parameter = new Entity\ApplicationLocalizationParameter();
			$parameter->setName($name);
			$parameter->setApplicationLocalization($this->applicationLocalization);
		}
		
		return $parameter;
	}

	protected function getParameterValue($name, $default = null)
	{
		$parameter = $this->findParameter($name);
		if ($parameter !== null) {
			return $parameter->getValue();
		}
		
		return $default;
	}
}