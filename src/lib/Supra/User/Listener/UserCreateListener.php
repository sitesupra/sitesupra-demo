<?php

namespace Supra\User\Listener;

use Supra\User\Event\UserCreateEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Mailer\Message\TwigMessage;
use Supra\User\Entity\User;

class UserCreateListener
{
	/**
	 * This method is triggered after CMS user is created in system
	 * @param UserCreateEventArgs $eventArgs
	 */
	public function postUserCreate(UserCreateEventArgs $eventArgs)
	{
		$userProviderInterface = ObjectRepository::INTERFACE_USER_PROVIDER; 
		$this->userProvider = ObjectRepository::getObject($this, $userProviderInterface, null); 
		if (is_null($this->userProvider)) {
			$this->userProvider = $eventArgs->getUserProvider();
			
			if (is_null($this->userProvider)) {
				throw new \Supra\Event\Exception\RuntimeException('CmsUserCreate listener has no user provider assigned');
			}
		}
		
		$user = $eventArgs->getUser();
		
		$this->generateAndSendNewUserEmail($user);
	}
	
	/**
	 * This method is triggered after PORTAL user is created in system
	 * using remote command
	 * @param UserCreateEventArgs $eventArgs
	 */
	public function portalUserPostCreate(UserCreateEventArgs $eventArgs)
	{
		$userProviderInterface = ObjectRepository::INTERFACE_USER_PROVIDER; 
		$this->userProvider = ObjectRepository::getObject($this, $userProviderInterface, null); 
		if (is_null($this->userProvider)) {
			$this->userProvider = $eventArgs->getUserProvider();
			
			if (is_null($this->userProvider)) {
				throw new \Supra\Event\Exception\RuntimeException('CmsUserCreate listener has no user provider assigned');
			}
		}
		
		$user = $eventArgs->getUser();
		
		$this->generateAndSendNewUserEmail($user);
	}
	
	/**
	 * Prepare password change link and send notification email with it
	 * @param UserCreateEventArgs $eventArgs
	 */
	protected function generateAndSendNewUserEmail(User $user)
	{
		$subject = 'New user account created';

		$time = time();
		$userMail = $user->getEmail();
		
		$hash = $this->userProvider->generatePasswordRecoveryHash($user, $time);
		
		$authAdapter = $this->userProvider->getAuthAdapter();

		$userLogin = null;

		if (is_callable(array($authAdapter, 'getDefaultDomain'))) {
			$domain = $authAdapter->getDefaultDomain();
			if (strpos($userMail, '@' . $domain) && ! empty($domain)) {
				$emailParts = explode('@', $userMail);
				$userLogin = $emailParts[0];
			}
		}

		$systemInfo = ObjectRepository::getSystemInfo($this);
		$host = $systemInfo->getHostName(\Supra\Info::WITH_SCHEME);

		// TODO: hardcoded CMS path
		$url = $host . '/' . SUPRA_CMS_URL . '/restore';
		$query = http_build_query(array(
			'e' => $userMail,
			't' => $time,
			'h' => $hash,
				));

		$mailVars = array(
			'link' => $url . '?' . $query,
			'email' => $userMail,
			'login' => $userLogin,
		);

		$mailer = ObjectRepository::getMailer($this);
		$message = new TwigMessage();

		$message->setContext(__CLASS__);

		$message->setSubject($subject)
				->setTo($userMail)
				->setBody("mail-template/createpassword.twig", $mailVars);
		$mailer->send($message);
	}
}
