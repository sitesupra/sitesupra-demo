<?php

namespace Supra\Cms;

use Supra\Controller\Pages\Event\CmsUserCreateEventArgs;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Mailer\Message\TwigMessage;

class CmsUserCreateListener
{
	public function postUserCreate(CmsUserCreateEventArgs $eventArgs)
	{
		$user = $eventArgs->getUser();
		
		$subject = 'New user account created';

		$time = time();
		$userMail = $user->getEmail();
		
		$userProvider = ObjectRepository::getUserProvider($this);
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
		$url = $host . '/cms/restore';
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
				->setBody("internal-user-manager/mail-template/createpassword.twig", $mailVars);
		$mailer->send($message);
	}
}
