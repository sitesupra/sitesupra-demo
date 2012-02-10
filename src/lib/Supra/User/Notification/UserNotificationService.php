<?php

namespace Supra\User\Notification;

use Supra\User\Entity\User;
use Supra\User\Entity\UserNotification;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Supra\User\UserProvider;
use Supra\ObjectRepository\ObjectRepository;

class UserNotificationService
{
	const EVENT_NAME_USER_NOTIFICATION_SENT = 'userNotificationSent';

	/**
	 * @var EntityManager
	 */
	protected $em;

	/**
	 * @var EntityRepository
	 */
	protected $userNotificationRepository;

	/**
	 * @var UserProvider;
	 */
	protected $userProvider;

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->em)) {
			$this->em = ObjectRepository::getEntityManager($this);
		}

		return $this->em;
	}

	/**
	 * @param EntityManager $em 
	 */
	public function setEntityManager(EntityManager $em)
	{
		$this->em = $em;
	}

	/**
	 * @return UserProvider
	 */
	public function getUserProvider()
	{
		return $this->userProvider;
	}

	/**
	 * @param UserProvider $userProvider 
	 */
	public function setUserProvider(UserProvider $userProvider)
	{
		$this->userProvider = $userProvider;
	}

	/**
	 * @return EntityRepository
	 */
	public function getUserNotificationRepository()
	{
		if (empty($this->userNotificationRepository)) {

			$this->userNotificationRepository = $this->getEntityManager()
					->getRepository(UserNotification::CN());
		}

		return $this->userNotificationRepository;
	}

	/**
	 * @param EntityRepository $repo 
	 */
	public function setUserNotificationRepository($repo)
	{
		$this->userNotificationRepository = $repo;
	}

	/**
	 * @param User $user
	 * @param UserNotification $notification 
	 */
	public function sendNotification(User $user, UserNotification $notification)
	{
		$em = $this->getEntityManager();

		$notification->setIsRead(false);
		$notification->setUser($user);

		$em->persist($notification);
		$em->flush();

		$this->fireUserNotificationServiceEvent(self::EVENT_NAME_USER_NOTIFICATION_SENT, $notification);
	}

	/**
	 * @param string $eventType
	 * @param UserNotification $notification 
	 */
	protected function fireUserNotificationServiceEvent($eventType, UserNotification $notification)
	{
		$eventManager = ObjectRepository::getEventManager($this);

		$eventArgs = new UserNotificationEventArgs();
		$eventArgs->setNotification($notification);

		$eventManager->fire($eventType, $eventArgs);
	}

	/**
	 * @param User $user
	 * @param integer $notificationType 
	 * @return array
	 */
	public function getUserNotifications(User $user, $notificationType)
	{
		$repo = $this->getUserNotificationRepository();

		$criteria = array(
			'user_id' => $user->getId()
		);

		$result = $repo->findBy($criteria);

		return $result;
	}

	protected function getUserNotificationSelctionQueryBuilder(User $user, $isRead, $isVisible)
	{
		$em = $this->getEntityManager();
		$qb = $em->createQueryBuilder();

		$qb->from(UserNotification::CN(), 'un')
				->where('un.user_id = :user_id')
				->andWhere('un.isRead = :isRead')
				->andWhere('un.isVisible = :isVisible');

		$qb->set('user_id', $user->getId());
		$qb->set('isRead', $isRead);
		$qb->set('isVisible', $isVisible);

		return $qb;
	}

	/**
	 * @param User $user 
	 * @return boolean
	 */
	public function hasUserUnreadNotifications(User $user, $isVisible = true)
	{
		$qb = $this->getUserNotificationSelctionQueryBuilder($user, false, $isVisible);

		$qb->select('count(un.id)');

		$count = $qb->getQuery()->getSingleScalarResult();

		$result = $count > 0;

		return $result;
	}

	/**
	 * @param User $user 
	 * @return array
	 */
	public function getUnreadNotificationsForUser(User $user, $isVisible = true)
	{
		$qb = $this->getUserNotificationSelctionQueryBuilder($user, false, $isVisible);

		$qb->select('un.*');

		$notifications = $qb->getQuery()->getResult();

		return $notifications;
	}

}
