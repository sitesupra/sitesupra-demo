<?php

namespace Supra\User\Listener;

use Supra\User\Event\UserCreateEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Mailer\Message\TwigMessage;

class UserCreateListener
{
	public function postUserCreate(UserCreateEventArgs $eventArgs)
	{
		$user = $eventArgs->getUser();
		
		$subject = 'New user account created';

		$time = time();
		$userMail = $user->getEmail();
		
		$userProviderInterface = ObjectRepository::INTERFACE_USER_PROVIDER; 
		$userProvider = ObjectRepository::getObject($this, $userProviderInterface, null); 
		if (is_null($userProvider)) {
			$userProvider = $eventArgs->getUserProvider();
			
			if (is_null($userProvider)) {
				throw new \Supra\Event\Exception\RuntimeException('CmsUserCreate listener has no user provider assigned');
			}
		}
		
		$hash = $userProvider->generatePasswordRecoveryHash($user, $time);
		
		$authAdapter = $userProvider->getAuthAdapter();

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
