<?php

namespace Supra\User;

use Supra\User\Entity;
use Supra\Authentication\AuthenticationPassword;
use Supra\Authentication\Exception\UserNotFoundException;
use Doctrine\ORM\UnitOfWork;
use SupraPortal\SiteUser\Entity as SiteEntity;
use SupraPortal\SiteUser\Entity\SiteUser;
use SupraPortal\SiteUser\Entity\SiteUserGroup;
use Doctrine\ORM\NoResultException;

class SiteUserProvider extends UserProviderAbstract
{

	/**
	 * Site key
	 * @var string
	 */
	private $siteKey = null;

	/**
	 * Site entity
	 * @var SiteEntity\Site
	 */
	private $siteEntity = null;

	/**
	 * Returns site entity
	 * @return type 
	 */
	public function getSiteEntity()
	{
		return $this->siteEntity;
	}

	/**
	 * Sets site entity
	 * @param SiteEntity\Site $siteEntity 
	 */
	public function setSiteEntity(SiteEntity\Site $siteEntity)
	{
		$this->siteEntity = $siteEntity;
		$this->siteKey = $siteEntity->getId();
	}

	/**
	 * @return \Doctrine\ORM\QueryBuilder 
	 */
	private function getDefaultQuery()
	{
		$qb = $this->getEntityManager()->createQueryBuilder();
		$qb->select('su')
				->from(SiteUser::CN(), 'su')
				->where('su.site = :site_key');

		$qb->setParameter('site_key', $this->getSiteKey());

		return $qb;
	}

	/**
	 * @return \Doctrine\ORM\QueryBuilder 
	 */
	private function getDefaultUserQuery()
	{
		$qb = $this->getDefaultQuery();
		$qb->addSelect('u')
				->join('su.user', 'u');

		return $qb;
	}

	/**
	 * @return \Doctrine\ORM\QueryBuilder 
	 */
	private function getDefaultGroupQuery()
	{
		$qb = $this->getDefaultQuery();
		$qb->addSelect('g')
				->join('su.userGroup', 'g');

		return $qb;
	}

	/**
	 * {@inheritDoc}
	 */
	public function authenticate($login, AuthenticationPassword $password)
	{
		$adapter = $this->getAuthAdapter();

		$login = $adapter->getFullLoginName($login);

		$user = $this->findUserByLogin($login);

//		// Try finding the user from adapter
//		if (empty($user)) {
//		$user = $adapter->findUser($login, $password);
//
//			if (empty($user)) {
//				throw new UserNotFoundException();
//			}
//
//			$entityManager = $this->getEntityManager();
//			$entityManager->persist($user);
//			$entityManager->flush();
//		}

		if (empty($user)) {
			throw new UserNotFoundException();
		}

		$adapter->authenticate($user, $password);

		return $user;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserByLogin($login)
	{
		$user = null;

		if ($login == SystemUser::LOGIN) {
			$user = new SystemUser();
		} else {
			$entityManager = $this->getEntityManager();
			$repo = $entityManager->getRepository(Entity\User::CN());
			$user = $repo->findOneByLogin($login);
		}

		if (empty($user)) {
			return null;
		}

		return $user;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserById($id)
	{
		if (empty($this->siteKey)) {
			$entityManager = $this->getEntityManager();

			return $entityManager->find(Entity\User::CN(), $id);
		} else {
		
			$qb = $this->getDefaultUserQuery();
			$qb->andWhere('su.user = :value');
			$qb->setParameter('value', $id);

			$result = array();
			try {
				$result = $qb->getQuery()->getSingleResult();
			} catch (NoResultException $e) {
				return null;
			}

			if ($result instanceof SiteUser) {
				return $result->getUser();
			}

			return null;
		}
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserByEmail($email)
	{
		$qb = $this->getDefaultUserQuery();
		$qb->andWhere('u.email = :value');
		$qb->setParameter('value', $email);

		$result = array();
		try {
			$result = $qb->getQuery()->getSingleResult();
		} catch (NoResultException $e) {
			return null;
		}

		if ($result instanceof SiteUser) {
			return $result->getUser();
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserByName($name)
	{
		$qb = $this->getDefaultUserQuery();
		$qb->andWhere('u.name = :value');
		$qb->setParameter('value', $name);

		$result = array();
		try {
			$result = $qb->getQuery()->getSingleResult();
		} catch (NoResultException $e) {
			return null;
		}

		if ($result instanceof SiteUser) {
			return $result->getUser();
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findGroupByName($name)
	{
		$qb = $this->getDefaultGroupQuery();

		$qb->andWhere('g.name = :value');
		$qb->setParameter('value', $name);

		$result = array();
		try {
			$result = $qb->getQuery()->getSingleResult();
		} catch (NoResultException $e) {
			return null;
		}

		if ($result instanceof SiteUser) {
			return $result->getGroup();
		}

		return null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findGroupById($id)
	{
		$qb = $this->getDefaultGroupQuery();

		$qb->andWhere('g = :value');
		$qb->setParameter('value', $id);

		$result = array();
		try {
			$result = $qb->getQuery()->getSingleResult();
		} catch (NoResultException $e) {
			return null;
		}

		if ($result instanceof SiteUser) {
			return $result->getGroup();
		}

		return null;
	}

	private function getDataFromResult($result, $entity)
	{
		$data = array();
		foreach ($result as $row) {
			if ( ! $row instanceof SiteUser) {
				continue;
			}

			if ($entity == Entity\User::CN()) {
				$user = $row->getUser();
				if ($user instanceof $entity) {
					$data[] = $user;
				}
			}

			if ($entity == Entity\Group::CN()) {
				$data[] = $row->getGroup();
			}
		}

		return $data;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findAllUsers()
	{
		$qb = $this->getDefaultUserQuery();

		$result = array();
		try {
			$result = $qb->getQuery()->getResult();
		} catch (NoResultException $e) {
			return null;
		}

		return $this->getDataFromResult($result, Entity\User::CN());
	}

	/**
	 * {@inheritDoc}
	 */
	public function findAllGroups()
	{
		$qb = $this->getDefaultGroupQuery();

		$result = array();
		try {
			$result = $qb->getQuery()->getResult();
		} catch (NoResultException $e) {
			return null;
		}

		return $this->getDataFromResult($result, Entity\Group::CN());
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllUsersInGroup(Entity\Group $group)
	{
//		$this->getEntityManager()->clear();

		$qb = $this->getDefaultUserQuery();
		$qb->join('su.userGroup', 'g')
				->addSelect('g')
				->andWhere('g = :group_id');

		$qb->setParameter('group_id', $group->getId());

		$result = array();
		try {
			$result = $qb->getQuery()->getDQL();
			$result = $qb->getQuery()->getResult();
		} catch (NoResultException $e) {
			return null;
		}

		return $this->getDataFromResult($result, Entity\User::CN());
	}

	/**
	 * {@inheritDoc}
	 */
	public function createUser()
	{
		$user = new Entity\User();

		return $user;
	}

	/**
	 * {@inheritDoc}
	 */
	public function createGroup()
	{
		$group = new Entity\Group();

		return $group;
	}

	/**
	 * {@inheritDoc}
	 */
	public function doInsertUser(Entity\User $user)
	{
		$entityManager = $this->getEntityManager();

		$entityManager->persist($user);

		if ($entityManager->getUnitOfWork()->getEntityState($user, null) != UnitOfWork::STATE_MANAGED) {
			throw new Exception\RuntimeException('Presented user entity is not managed');
		}

		$entityManager->flush();
	}

	/**
	 * {@inheritDoc}
	 */
	protected function doInsertGroup(Entity\Group $group)
	{

		if ( ! ($this->siteEntity instanceof SiteEntity\Site)) {
			throw new Exception\RuntimeException('Can not make new site-group; site entity not provided.');
		}

		$siteuserGroup = new SiteUserGroup();
		$siteuserGroup->setGroup($group);
		$siteuserGroup->setSite($this->siteEntity);

		$entityManager = $this->getEntityManager();
		$entityManager->persist($group);
		$entityManager->persist($siteuserGroup);

		if ($entityManager->getUnitOfWork()->getEntityState($group, null) != UnitOfWork::STATE_MANAGED) {
			throw new Exception\RuntimeException('Presented group entity is not managed');
		}

		$entityManager->flush();
	}

	/**
	 * {@inheritDoc}
	 */
	public function doDeleteUser(Entity\User $user)
	{
		$entityManager = $this->getEntityManager();

		$entityManager->remove($user);
		$entityManager->flush();
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateUser(Entity\User $user)
	{
		$entityManager = $this->getEntityManager();

		if ($entityManager->getUnitOfWork()->getEntityState($user, null) != UnitOfWork::STATE_MANAGED) {
			throw new Exception\RuntimeException('Presented user entity is not managed');
		}

		$entityManager->flush();
	}

	/**
	 * {@inheritDoc}
	 */
	public function updateGroup(Entity\Group $group)
	{
		$entityManager = $this->getEntityManager();

		if ($entityManager->getUnitOfWork()->getEntityState($group, null) != UnitOfWork::STATE_MANAGED) {
			throw new Exception\RuntimeException('Presented group entity is not managed');
		}

		$entityManager->flush();
	}

	/**
	 * Returns site key value
	 * @return string
	 */
	public function getSiteKey()
	{
		if ( ! empty($this->siteKey)) {
			return $this->siteKey;
		}

		throw new Exception\LogicException('Site key not defined; siteKey and siteEntity->id values are null');
	}

	/**
	 * Sets site key vslue
	 * @param string $siteKey 
	 */
	public function setSiteKey($siteKey)
	{
		$this->siteKey = $siteKey;
		
		if ( ! empty($this->siteEntity) && $this->siteEntity->getId() != $siteKey) {
			$this->siteEntity = null;
		}
	}

	/**
	 * Cretae and returns new Site entity
	 * @param string $subdomainUrl
	 * @return SupraPortal\SiteUser\Entity\Site 
	 */
	public function createSite($subdomainUrl)
	{

		$siteEntity = new SiteEntity\Site();
		$this->getEntityManager()->persist($siteEntity);
		$siteEntity->setSubdomainUrl($subdomainUrl);
		$this->setSiteEntity($siteEntity);

		return $siteEntity;
	}

}
