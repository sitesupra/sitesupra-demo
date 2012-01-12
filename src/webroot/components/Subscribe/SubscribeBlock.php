<?php

namespace Project\Subscribe;

use Supra\Controller\Pages\Entity;
use Supra\Controller\Pages\BlockController;
use Supra\Request;
use Supra\Response;
use Supra\Mailer;
use Supra\Mailer\Message;
use Supra\Mailer\Message\TwigMessage;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Uri\Path;
use Supra\Mailer\CampaignMonitor\Entity\Subscriber;
use Supra\Validator\Exception;

/**
 * Description of SubscribeBlock
 *
 * @author aleksey
 */
class SubscribeBlock extends BlockController
{
	const ACTION_SUBSCRIBE = 'subscribe';
	const ACTION_UNSUBSCRIBE = 'unsubscribe';
	const ACTION_CONFIRM_SUBSCRIBE = 'confirm_subscribe';
	const ACTION_CONFIRM_UNSUBSCRIBE = 'confirm_unsubscribe';

	/**
	 * Current request
	 * @var PageRequest
	 */
	protected $request;

	/**
	 * Current response
	 * @var Response\TwigResponse
	 */
	protected $response;

	public function execute()
	{
		$this->request = $this->getRequest();
		$this->response = $this->getResponse();

		$action = $this->request->getParameter('action');

		// Selecting subscribe-action
		switch ($action) {

			case self::ACTION_CONFIRM_SUBSCRIBE: {

					$this->actionConfirmSubscribe();
				}break;
			case self::ACTION_CONFIRM_UNSUBSCRIBE: {

					$this->actionConfirmUnsubscribe();
				}break;
			case self::ACTION_UNSUBSCRIBE: {

					$this->actionUnsubscribe();
				}break;
			default : {

					$this->actionSubscribe();
				}
		}

		// Local file is used
		$this->response->outputTemplate('index.html.twig');
	}

	/**
	 * Action for subscribe user
	 * @return null
	 */
	protected function actionSubscribe()
	{
		$error = null;
		$this->response->assign('action', self::ACTION_SUBSCRIBE);

		if ($this->request->isPost()) {

			$this->response->assign('postedData', true);
			$postData = $this->request->getPost();

			try {
				$email = $postData->getValid('email', \Supra\Validator\Type\AbstractType::EMAIL);
			} catch (Exception\ValidationFailure $e) {
				$error[] = 'wrong_email_address';
			}

			$this->response->assign('email', $postData->get('email', ''));

			$subscriberName = $postData->get('name', '');
			$subscriberName = trim($subscriberName);
			$this->response->assign('name', $subscriberName);

			if (empty($subscriberName)) {
				$error[] = 'empty_subscriber_name';
			}

			//Check is subscriber already exists and active

			$activeSubscriber = $this->getSubscriber($email, null, true);

			/**
			 * @todo how to handle this case?
			 */
			if ( ! empty($activeSubscriber)) {
				$error[] = 'subscriber_alredy_active';
			}

			if ( ! empty($error)) {
				$this->response->assign('error', $error);
				return;
			}

			/* @var $localization PageLocalization */
			$localization = $this->getRequest()->getPageLocalization();

			if ( ! ($localization instanceof Entity\PageLocalization)) {
				return null;
			}

			//Remove old unconfirmed records
			$this->deleteSubscriber($email, $hash = null, $active = false);

			//Create new subscriber
			$subscriber = $this->createSubscriber($email, $subscriberName);
			$hash = $subscriber->getConfirmHash();

			//Create url for confirm subscribe
			$url = $this->prepareMessageUrl($email, $hash, self::ACTION_CONFIRM_SUBSCRIBE, $localization);

			/**
			 * @todo get subject and mail from-addres from configuration
			 */
			$emailParams = array(
				'subject' => $this->getPropertyValue('confirmSubscribeSubject'),
				'name' => $subscriberName,
				'link' => $url,
				'email' => $email);

			try {
				$this->sendEmail($emailParams, 'confirm_subscribe');
			} catch (\Exception $e) {
				$this->log->error("Can't send email on subscribe action; ", (string) $e);
				$error[] = 'cant_send_mail';
			}

			if (empty($error)) {
				$entityManager = ObjectRepository::getEntityManager($this);
				$entityManager->persist($subscriber);
				$entityManager->flush();
			} else {
				$this->response->assign('error', $error);
			}
		}
	}

	/**
	 * Unsubscribe action
	 * @return void
	 */
	protected function actionUnsubscribe()
	{
		$error = null;
		$this->response->assign('action', self::ACTION_UNSUBSCRIBE);

		if ($this->request->isPost()) {

			$this->response->assign('postedData', true);
			$postData = $this->request->getPost();

			try {
				$email = $postData->getValid('email', \Supra\Validator\Type\AbstractType::EMAIL);
				/**
				 * @todo add required exception type
				 */
			} catch (Exception\ValidationFailure $e) {
				$error[] = 'wrong_email_address';
				$this->response->assign('email', $postData->get('email', ''));
				$this->response->assign('error', $error);
				return;
			}

			$this->response->assign('email', $postData->get('email', ''));


			/* @var $localization PageLocalization */
			$localization = $this->getRequest()->getPageLocalization();

			if ( ! ($localization instanceof Entity\PageLocalization)) {
				return;
			}

			//Get subscriber
			$subscriber = $this->getSubscriber($email, null, true);

			if (empty($subscriber)) {
				$error[] = 'subscriber_not_found';
				$this->response->assign('error', $error);
				return;
			} else {
				$subscriber = $subscriber[0];
			}

			$subscriber->generateConfirmHash();
			$hash = $subscriber->getConfirmHash();
			$subscriberName = $subscriber->getName();

			//Create url for confirm unsubscribe
			$url = $this->prepareMessageUrl($email, $hash, self::ACTION_CONFIRM_UNSUBSCRIBE, $localization);

			/**
			 * @todo get subject and mail from-addres from configuration
			 */
			$emailParams = array(
				'subject' => $this->getPropertyValue('confirmUnsubscribeSubject'),
				'name' => $subscriberName,
				'link' => $url,
				'email' => $email);

			try {
				$this->sendEmail($emailParams, 'confirm_unsubscribe');
			} catch (\Exception $e) {
				$this->log->error("Can't send email on unsubscribe action; ", (string) $e);
				$error[] = 'cant_send_mail';
			}

			if (empty($error)) {
				$entityManager = ObjectRepository::getEntityManager($this);
				$entityManager->persist($subscriber);
				$entityManager->flush();
			}

			$this->response->assign('error', $error);
		}
	}

	/**
	 * Confirm subscribe action
	 */
	protected function actionConfirmSubscribe()
	{
		$error = false;

		$email = $this->request->getParameter('email', '');
		$hash = $this->request->getParameter('hash', '');

		$confirmedSubscriber = $this->confirmSubscribe($email, $hash);

		if ( ! ($confirmedSubscriber instanceof Subscriber)) {
			$error = true;
		} else {
			$this->response->assign('name', $confirmedSubscriber->getName());
		}

		$this->response->assign('email', $email);
		$this->response->assign('error', $error);
		$this->response->assign('action', self::ACTION_CONFIRM_SUBSCRIBE);
	}

	/**
	 * Action to confirm unsubscribe
	 */
	protected function actionConfirmUnsubscribe()
	{

		$error = false;

		$email = $this->request->getParameter('email', '');
		$hash = $this->request->getParameter('hash', '');

		$confirmedSubscriber = $this->confirmUnsubscribe($email, $hash);

		if ( ! ($confirmedSubscriber instanceof Subscriber)) {
			$error = true;
		} else {
			$this->response->assign('name', $confirmedSubscriber->getName());
			$entityManager = ObjectRepository::getEntityManager($this);
			$entityManager->flush();
		}

		$this->response->assign('error', $error);
		$this->response->assign('email', $email);
		$this->response->assign('action', self::ACTION_CONFIRM_UNSUBSCRIBE);
	}

	/**
	 * Confirm subscriber - set it as active
	 * @param string $email
	 * @param string $hash
	 * @return \Supra\Mailer\CampaignMonitor\Entity\Subscriber 
	 */
	protected function confirmSubscribe($email, $hash)
	{
		$subscriber = $this->getSubscriber($email, $hash, false);

		if (empty($subscriber)) {
			return;
		}

		$subscriber = $subscriber[0];
		$subscriber->setActive(true);
		$entityManager = ObjectRepository::getEntityManager($this);
		$entityManager->persist($subscriber);
		$entityManager->flush();

		return $subscriber;
	}

	/**
	 * Unsubscribe user 
	 * @param string $email
	 * @param string $hash
	 * @return \Supra\Mailer\CampaignMonitor\Entity\Subscriber 
	 */
	protected function confirmUnsubscribe($email, $hash)
	{
		$subscriber = $this->getSubscriber($email, $hash, true);

		if ( ! empty($subscriber)) {
			$this->deleteSubscriber($email);
			return $subscriber[0];
		}

		return null;
	}

	/**
	 * Send email
	 * @param array $emailParams
	 * @param string $templateName 
	 */
	private function sendEmail($emailParams, $templateName)
	{

		$mailer = ObjectRepository::getMailer($this);
		$message = new TwigMessage();
		$message->setContext(__CLASS__);

		$message->setSubject($emailParams['subject'])
				->setFrom($this->getPropertyValue('emailFromAddress'), $this->getPropertyValue('emailFromName'))
				->setTo($emailParams['email'], $emailParams['name'])
				->setBody("mail-template/{$templateName}.twig", $emailParams, 'text/html');

		$mailer->send($message);
	}

	/**
	 * Return subscribers by parameters
	 * @param string $email
	 * @param string|null $hash
	 * @param bool|null $active
	 * @return Supra\Mailer\CampaignMonitor\Entity\Subscriber[]
	 */
	private function getSubscriber($email, $hash = null, $active = null)
	{

		$entityManager = ObjectRepository::getEntityManager($this);
		$repo = $entityManager->getRepository('Supra\Mailer\CampaignMonitor\Entity\Subscriber');

		$params = array('emailAddress' => $email);

		if ( ! empty($hash)) {
			$params['confirmHash'] = $hash;
		}

		if ($active !== null) {
			$params['active'] = (bool) $active;
		}

		$result = $repo->findBy($params);

		return $result;
	}

	/**
	 * Delete subscriber
	 * @param string $email
	 * @param string|null $hash 
	 * @param bool|null $active
	 */
	private function deleteSubscriber($email, $hash = null, $active = null)
	{

		$entityManager = ObjectRepository::getEntityManager($this);
		$subscribers = $this->getSubscriber($email, $hash, $active);

		foreach ($subscribers as $entity) {
			$entityManager->remove($entity);
		}
	}

	/**
	 * Create new subscriber
	 * @param string $email
	 * @param string $subscriberName
	 * @return \Supra\Mailer\CampaignMonitor\Entity\Subscriber 
	 */
	private function createSubscriber($email, $subscriberName)
	{

		$subscriber = new Subscriber();
		$subscriber->setEmailAddress($email);
		$subscriber->setName($subscriberName);
		$subscriber->setActive(false);
		$subscriber->generateConfirmHash();

		return $subscriber;
	}

	/**
	 *
	 * @param string $email
	 * @param string $hash
	 * @param string $action
	 * @param PageLocalization $localization
	 * @return string
	 */
	private function prepareMessageUrl($email, $hash, $action, $localization)
	{
		$url = ObjectRepository::getSystemInfo($this)
				->getHostName(\Supra\Info::WITH_SCHEME);
		$url.= $localization->getPath()->
				getFullPath(Path::FORMAT_BOTH_DELIMITERS);
		$url.= "?hash={$hash}&email={$email}&action=";
		$url.= $action;

		return $url;
	}

	/**
	 * Loads property definition array
	 * @return array
	 */
	public function getPropertyDefinition()
	{
		$contents = array();

		$hostName = ObjectRepository::getSystemInfo($this)
				->getHostName(\Supra\Info::NO_SCHEME);

		$stringValue = new \Supra\Editable\String("Email from address");
		$stringValue->setDefaultValue('no-reply@' . $hostName);
		$contents['emailFromAddress'] = $stringValue;

		$stringValue = new \Supra\Editable\String("Email from name");
		$stringValue->setDefaultValue($hostName);
		$contents['emailFromName'] = $stringValue;

		$stringValue = new \Supra\Editable\String("Email subscribe confirm subject");
		$stringValue->setDefaultValue('Confirm subscription');
		$contents['confirmSubscribeSubject'] = $stringValue;

		$stringValue = new \Supra\Editable\String("Email unsubscribe confirm subject");
		$stringValue->setDefaultValue('Confirm unsubscribe');
		$contents['confirmUnsubscribeSubject'] = $stringValue;

		return $contents;
	}

}
