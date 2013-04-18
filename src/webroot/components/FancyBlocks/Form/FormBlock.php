<?php

namespace Project\FancyBlocks\Form;

use Supra\Controller\Pages\BlockController;
use Supra\Validator\FilteredInput;
use Supra\Validator\Type\AbstractType;

/**
 * @FIXME: kill this code with fire
 *		write the normal form builder
 */
class FormBlock extends BlockController
{

	public function doExecute()
    {
		$request = $this->getRequest();
		/* @var $request \Supra\Request\HttpRequest */
		if ($request->getQuery()
				->offsetExists('captcha')) {
			
			$this->renderCaptcha();
			return;
		}
		
		$fields = $this->getPropertyValue('fields');
		foreach ($fields as $offset => &$field) {
			$hash = $this->getFieldHash($offset, $field);
			$field['hash'] = $hash;
		}
		
        $response = $this->getResponse();
		$response->assign('fields', $fields);
		
		
		if ($this->getRequest()->isPost()) {
			$this->handleFormPost();
		}
		
        $response->outputTemplate('index.html.twig');
    }

	/**
	 * 
	 */
	protected function handleFormPost()
	{
		$response = $this->getResponse();
				
		$post = $this->getRequest()
				->getPost();
		
		$blockId = $this->getBlock()
				->getId();
			
		if ( ! $post->offsetExists($blockId)) {
			return;
		}
		
		$postArray = $post->getArrayCopy();
		
		
		$mandatoryFieldsErrors = $this->collectRequiredFieldErrors($postArray);
		if ( ! empty($mandatoryFieldsErrors)) {
			$response->assign('errors', $mandatoryFieldsErrors);
			return;
		}
		
//		$captcha = $post->get('captcha');
//		if ( ! $this->isCaptchaValid($captcha)) {
//			$response->assign('invalidCaptcha')
//				->outputTemplate('index.html.twig');
//			
//			return;
//		}
		
		$fieldsData = $this->collectFieldsData($postArray);
		
		$receiverEmail = $this->getValidReceiverEmail();
		
		if ( ! empty($receiverEmail)) {
		
			$mailer = \Supra\ObjectRepository\ObjectRepository::getMailer($this);

			$message = new \Supra\Mailer\Message\TwigMessage('text/html');
			$message->setContext(__CLASS__);

			$siteName = \Supra\ObjectRepository\ObjectRepository::getSystemInfo($this)
					->getName();
			
			$message->setTo($receiverEmail)
					->setSubject("New message from \"{$siteName}\" project")
					->setBody('mail.html.twig', array('postedFields' => $fieldsData));
			
			$mailer->send($message);
		}
		
		$response->assign('confirmation', true);
	}
	
	/**
	 * @return string|null
	 */
	protected function getValidReceiverEmail()
	{
		$email = $this->getPropertyValue('email');
		
		try {
			FilteredInput::validate($email, AbstractType::EMAIL);
		} catch (\Exception $e) {
			return null;
		}
		
		return $email;
	}
	
	protected function renderCaptcha()
	{
		$captcha = new \SupraSite\Captcha\Captcha();
		
		$captcha->setKeyPrefix($this->getBlock()->getId());
		
		$captcha->setBackgroundColor(0x00FFFFFF);
		$captcha->setFontColor(0x004B4232);
		
		$captcha->renderAndOutput();
	}
	
	protected function isCaptchaValid($key)
	{
		$isRequired = $this->getPropertyValue('captcha');
		
		if ($isRequired) {
			$captcha = new \SupraSite\Captcha\Captcha();
			$captcha->setKeyPrefix($this->getBlock()->getId());
			
			if ($captcha->validateKey($key)) {
				return true;
			}
			
			return false;
		}
		
		return true;
	}
	
	protected function collectFieldsData($postData)
	{
		$fieldsData = array();
		
		$fields = $this->getPropertyValue('fields');
		
		foreach ($fields as $key => $field) {
			
			$hash = $this->getFieldHash($key, $field);
			
			if (isset($postData[$hash])) {
				$fieldsData[$field['title']] = $postData[$hash];
			}		
		}
		
		return $fieldsData;
	}
	
	protected function collectRequiredFieldErrors($postData)
	{
		$errors = array();
		
		$fields = $this->getPropertyValue('fields');
		
		foreach ($fields as $key => $field) {
		
			$hash = $this->getFieldHash($key, $field);
			
			if ($field['required'] && ( ! isset($postData[$hash]) || empty($postData[$hash]))) {
				$errors[$hash] = true;
			}	
		}
		
		return $errors;
	}
	
	/**
	 * @param integer $offset
	 * @param array $definition
	 * @return string
	 */
	private function getFieldHash($offset, $definition)
	{
		return md5(implode('', array(
			$offset,
			$definition['required'],
			$definition['title'],
			$definition['type'],
		)));
	}
	
}
