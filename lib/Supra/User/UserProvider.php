<?php

namespace Supra\User;

use Supra\User\Entity;
use Supra\Authentication\AuthenticationPassword;
use Supra\Authentication\Exception\UserNotFoundException;
use Doctrine\ORM\UnitOfWork;
use Supra\ObjectRepository\ObjectRepository;

class UserProvider extends UserProviderAbstract
{
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
		$entityManager = $this->getEntityManager();

		return $entityManager->find(Entity\User::CN(), $id);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserByEmail($email)
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\User::CN());
		$user = $repo->findOneByEmail($email);

		if (empty($user)) {
			return null;
		}
		return $user;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findUserByName($name)
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\User::CN());
		$user = $repo->findOneByName($name);

		if (empty($user)) {
			return null;
		}
		return $user;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findGroupByName($name)
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\Group::CN());
		$group = $repo->findOneByName($name);

		return $group;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findGroupById($id)
	{
		$entityManager = $this->getEntityManager();

		return $entityManager->find(Entity\Group::CN(), $id);
	}

	/**
	 * {@inheritDoc}
	 */
	public function findAllUsers()
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\User::CN());
		$users = $repo->findAll();

		return $users;
	}

	/**
	 * {@inheritDoc}
	 */
	public function findAllGroups()
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\Group::CN());
		$groups = $repo->findAll();

		return $groups;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAllUsersInGroup(Entity\Group $group)
	{
		$entityManager = $this->getEntityManager();
		$repo = $entityManager->getRepository(Entity\User::CN());
		$users = $repo->findBy(array('group' => $group->getId()));

		return $users;
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
	public function createGroup()
	{
		$group = new Entity\Group();

		return $group;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function doInsertGroup(Entity\Group $group)
	{
		$entityManager = $this->getEntityManager();
		
		$entityManager->persist($group);

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
			throw new Exception\RuntimeException('Presented user entity is not managed. Call insertUser instead of updateUser for new user entities.');
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
			throw new Exception\RuntimeException('Presented group entity is not managed. Call insertGroup instead of updateGroup for new user entities.');
		}

		$entityManager->flush();
	}

}
