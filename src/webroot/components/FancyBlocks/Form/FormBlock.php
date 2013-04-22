<?php

namespace Project\FancyBlocks\Form;

use Supra\Controller\Pages\BlockController;
use Supra\Validator\FilteredInput;
use Supra\Validator\Type\AbstractType;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Request\HttpRequest;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Mailer\Message\TwigMessage;

/**
 * @FIXME: kill this code with fire
 *		write the normal form builder
 */
class FormBlock extends BlockController
{
	/**
	 * 
	 */
	const CAPTCHA_SPACE_PARAMETER = 'captcha_%s';
	
	/**
	 * @var array
	 */
	private $formFields;
	
	/**
	 *
	 */
	protected function doPrepare()
	{
		$request = $this->getRequest();
		$context = $this->getResponse()
				->getContext();
		
		$cache = true;
		
		if ($this->isThisBlockPostRequest($request)
				|| $this->isCaptchaRequest($request)) {
			
			$cache = false;
		}
		
		$context->setValue('__FormBlockCache', $cache);
	}
	
	/**
	 * 
	 */
	protected function doExecute()
    {
		$request = $this->getRequest();
		
		if ($this->isCaptchaRequest($request)) {
			$this->renderImage();
			return;
		}
		
		$response = $this->getResponse();
		
		$formFields = $this->getFormFieldData();
		$response->assign('fields', $formFields);
		
		if ($this->isThisBlockPostRequest($request)) {
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
				
		$postData = $this->getRequest()
				->getPost()
				->getArrayCopy();
		

		$mandatoryFieldsErrors = $this->collectEmptyRequiredFields($postData);
		
		if ( ! empty($mandatoryFieldsErrors)) {
			$response->assign('errors', $mandatoryFieldsErrors);
			return;
		}
		
		$captcha = $this->getRequest()->getPost()->get('captcha', null);
		if ( ! $this->isCaptchaValid($captcha)) {
			$response->assign('invalidCaptcha');
			return;
		}
		
		$receiver = $this->getValidReceiverEmail();
		
		if ( ! empty($receiver)) {

			$mailer = ObjectRepository::getMailer($this);

			$message = new TwigMessage('text/html');
			$message->setContext(__CLASS__);

			$siteName = ObjectRepository::getSystemInfo($this)
					->getName();
			
			$fieldValues = $this->collectFieldValuesFromPost($postData);
			
			$message->setTo($receiver)
					->setSubject("New message from \"{$siteName}\" project")
					->setBody('mail.html.twig', array('postedFields' => $fieldValues));
			
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
	
	/**
	 * @param array $postData
	 * @return array
	 */
	protected function collectFieldValuesFromPost($postData)
	{
		$values = array();
		
		$fields = $this->getFormFieldData();
		
		foreach ($fields as $fieldData) {

			$hash = $fieldData['hash'];
			if (isset($postData[$hash])) {
				$values[$fieldData['title']] = $postData[$hash];
			}		
		}
		
		return $values;
	}
	
	/**
	 * @param array $postData
	 * @return array
	 */
	private function collectEmptyRequiredFields($postData)
	{
		$emptyFields = array();
		
		$fields = $this->getFormFieldData();
		foreach ($fields as $fieldData) {
			if ($fieldData['required']) {
				
				$hash = $fieldData['hash'];
				if (empty($postData[$hash])) {
					$emptyFields[] = $hash;
				}
			}
		}
		
		return $emptyFields;
	}
	
	/**
	 * 
	 */
	protected function getFormFieldData()
	{
		if ($this->formFields === null) {
			
			$this->formFields = array();
			
			$fieldSet = $this->getPropertyValue('fields');
			
			if ( ! empty($fieldSet)) {
				foreach ($fieldSet as $offset => &$field) {
					$hash = $this->getFieldHash($offset, $field);
					$field['hash'] = $hash;
				}
				$this->formFields = $fieldSet;
			}
		}
		
		return $this->formFields;
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
	
	/**
	 * @param \Project\FancyBlocks\Form\HttpRequest $request
	 * @return boolean
	 */
	private function isThisBlockPostRequest(HttpRequest $request)
	{
		if ($request->isPost() && $request->getPost()
				->offsetExists($this->getBlock()->getId())) {
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param \Supra\Request\HttpRequest $request
	 * @return boolean
	 */
	private function isCaptchaRequest(HttpRequest $request)
	{
		if ($request instanceof PageRequest
				&& $request->isBlockRequest()
				&& $request->getQuery()->offsetExists('captcha')) {
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Hardcoded and temporary solution for SupraSite
	 */
	protected function renderImage()
	{
		$captcha = new \SupraSite\Captcha\Captcha();
		
		$parameterSet = ObjectRepository::getThemeProvider($this)
				->getCurrentTheme()
				->getActiveParameterSet()
				->getValues();
		
		if ($parameterSet->containsKey('primaryColor')) {
			$color = $parameterSet->get('primaryColor')
					->getValue();
			
			$captcha->setFontColor($color);
		}

		$hash = $captcha->generateKeyAndGetHash();
		$parameterName = sprintf(self::CAPTCHA_SPACE_PARAMETER, $this->getBlock()->getId());
		
		$sessionManager = ObjectRepository::getSessionManager($this);
		$space = $sessionManager->getSessionNamespace(__NAMESPACE__);
		
		$space->__set($parameterName, $hash);
		
		$captcha->renderAndOutput();
		$sessionManager->close();
	}
	
	private function isCaptchaValid($code = null)
	{
		$isRequired = $this->getPropertyValue('captcha');
		
		if ($isRequired) {
			
			$parameterName = sprintf(self::CAPTCHA_SPACE_PARAMETER, $this->getBlock()->getId());

			$sessionManager = ObjectRepository::getSessionManager($this);
			$space = $sessionManager->getSessionNamespace(__NAMESPACE__);

			$storedHash = $space->__get($parameterName);
			if ( ! empty($storedHash) && sha1(strtolower($code)) == $storedHash) {
				$space->__unset($parameterName);
				$space->close();
				return true;
			}
			
			$space->__unset($parameterName);
			$space->close();
			return false;
		}
		
		return true;
	}
	
}
