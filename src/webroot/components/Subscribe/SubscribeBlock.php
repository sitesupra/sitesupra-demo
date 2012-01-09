<?php

namespace Project\Subscribe;

use Supra\Controller\Pages\BlockController,
	Supra\Request,
	Supra\Response;

use Supra\Mailer;
use Supra\Mailer\Message;
use Supra\ObjectRepository;
use Supra\Uri\Path;

/**
 * Description of SubscribeBlock
 *
 * @author aleksey
 */
class SubscribeBlock extends BlockController
{
	
	const SALT = 'h5$|zQ';
	
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

	protected function actionSubscribe(Response\TwigResponse $response)
	{	
		$error = null;
		
		if($this->request->isPost()) {
							
			$postData = $this->request->getPost();
			
			try{
				
				$email = $postData->getValid('email', \Supra\Validator\Type\AbstractType::EMAIL);
				
			} catch(\Exception $e) {
				$error[] = 'wrong_email_address';
			}
			
			$subscriberName = $postData->get('email');
			
			//Store subscriber
			
			$subscriber = new \Supra\Mailer\CampaignMonitor\Entity\Subscriber();
			
			$subscriber->setEmailAddress($email);
			$subscriber->setName($subscriberName);
			$subscriber->setActive(false);
			$subscriberId = $subscriber->getId();
			
			$entityManager = ObjectRepository::getEntityManager($this);
			$entityManager->persist($subscriber);
			$entityManager->flush();
			
			$hash = $this->getHash($email, $subscriberId);
			
			/* @var $localization PageLocalization */
			$localization = $this->getRequest()->getPageLocalization();
			
			if( ! ($localization instanceof PageLocalization)) {
				return null;
			}
			
			$url = $localization->getPath()->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
			
			$url.="?hash={$hash}&email={$email}"; 
			
			$emailParams = array (
					'name' => $subscriberName,
					'link' => $url,
					'email' => $email);
			
			$this->sendEmail($emailParams, 'confirm_subscribe');
			
			$this->response->assign('postedData', true);
		}
		
		$this->response->assign('errors', $error);
		$this->response->assign('action', self::ACTION_SUBSCRIBE);
	}

	protected function actionUnsubscribe(Response\TwigResponse $response)
	{
		$this->$response->assign('action', self::ACTION_UNSUBSCRIBE);
	}

	protected function actionConfirmSubscribe(Response\TwigResponse $response)
	{
		$result = $this->confirm();
		
		if($result) {
			
			$this->$response->assign('confirmed', true);	
			
		} else {
			
			$this->$response->assign('confirmed', false);	
			
		}
		
		$this->$response->assign('action', self::ACTION_CONFIRM_SUBSCRIBE);	
	}

	protected function actionConfirmUnsubscribe(Response\TwigResponse $response)
	{
		$this->$response->assign('action', self::ACTION_CONFIRM_UNSUBSCRIBE);			
	}

	protected function confirm()
	{
		
	}

	protected function subscribe()
	{
		
	}

	protected function unsubscribe()
	{
		
	}

	public function getHash($emailAddress, $userRecordId = '')
	{
		$hash = mb_strtolower($emailAddress) . ' ' . $userRecordId . self::SALT;
		$hash = md5($hash);
		$hash = substr($hash, 0, 8);
		return $hash;
	}
	
	
	
	private function sendEmail($emailParams, $templateName){
			
			$mailer = ObjectRepository::getMailer($this);
			$message = new TwigMessage();
			$message->setContext(__CLASS__);
			
			$message->setSubject($emailParams['subject'])
					->setTo($emailParams['subject'])
					->setBody("mail-template/{$templateName}.twig", $emailParams);
					
			$mailer->send($message);
	}
	

	
	
}
