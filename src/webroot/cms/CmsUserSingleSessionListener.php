<?php

namespace Supra\Cms;

use Supra\User\Event\UserEventArgs;
use Supra\User\Entity\UserSession;

/**
 * Checks if only one session exists per user 
 */
class CmsUserSingleSessionListener
{
	/**
	 * Removes all currently active sessions
	 * @param UserEventArgs $eventArgs
	 */
	public function preSignIn(UserEventArgs $eventArgs)
	{
		$entityManager = $eventArgs->entityManager;
		$user = $eventArgs->user;
		
		// Remove all active sessions of the user
		//TODO: should be configurable if do it and should check the access times
		$userSessionEntity = UserSession::CN();
		$query = $entityManager->createQuery(
				"DELETE FROM $userSessionEntity s WHERE s.user = ?0");
		$query->execute(array($user->getId()));
	}
}
