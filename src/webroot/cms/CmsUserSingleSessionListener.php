<?php

namespace Supra\Cms;

use Supra\User\Event\UserEventArgs;
use Supra\User\Entity\UserSession;
use Supra\Authentication\Exception\ExistingSessionLimitation;

/**
 * Checks if only one session exists per user 
 */
class CmsUserSingleSessionListener
{
	/**
	 * After how many seconds the session can be removed when new sign in happens
	 * @var int
	 */
	public $activityExpiration = 60;
	
	/**
	 * Removes all currently active sessions
	 * @param UserEventArgs $eventArgs
	 * @throws ExistingSessionLimitation
	 */
	public function preSignIn(UserEventArgs $eventArgs)
	{
		$entityManager = $eventArgs->entityManager;
		$user = $eventArgs->user;
		
		// Remove all active sessions of the user
		//TODO: should be configurable if do it and should check the access times
		$userSessionEntity = UserSession::CN();
		$params = array($user->getId());
		
		$lastActivityQuery = $entityManager->createQuery(
				"SELECT MAX(s.lastActivityTime) AS lastActivityTime FROM $userSessionEntity s WHERE s.user = ?0");
		$lastActivityQuery->execute($params);
		$lastActivity = $lastActivityQuery->getSingleScalarResult();
		
		// Don't allow sign in if activity is too recent
		if ( ! empty($lastActivity)) {
			$now = new \DateTime();
			$lastActivityTime = new \DateTime($lastActivity);
			
			$diff = time() - $lastActivityTime->getTimestamp();
			
			if ($diff < $this->activityExpiration) {
				throw new ExistingSessionLimitation("Active user session exists for this user already");
			}
		}
		
		$query = $entityManager->createQuery(
				"DELETE FROM $userSessionEntity s WHERE s.user = ?0");
		$query->execute($params);
	}
}
