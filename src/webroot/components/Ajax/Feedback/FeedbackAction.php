<?php

namespace Project\Ajax\Feedback;

use Supra\Controller\SimpleController;
use Supra\Validator\Type\EmailType;
use Supra\Validator\Exception\ValidatorException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Validator\Exception\ValidationFailure;
use Supra\Validator\Type\AbstractType;
use Project\Feedback\FeedbackBlock;


/**
 * Controller class, that handles feedback form posted data
 * It is impossible to merge this class methods with FeedbackBlock controller
 * as we have no possibility to output clean json output from FeedbackBlock itself
 * 
 * TODO: should we move this controller under Project\Feedback namespace (folder)?
 */
class FeedbackAction extends SimpleController
{
	/**
	 * Main method, processes posted values, validates it
	 */
	public function indexAction()
	{
		$request = $this->getRequest();
		$post = $request->getPost();
		
		$feedbackBlock = new FeedbackBlock();
		$formInputs = $feedbackBlock->getFormInputs();
		
		$postFields = array_keys($post->getArrayCopy());
		
		$errorArray = array(); $postValues = array();
		foreach($postFields as $fieldName) {
			if ( ! isset($formInputs[$fieldName])) {
				continue;
			}
			
			$errors = $this->checkIsInputValid($fieldName, $formInputs[$fieldName]);
			if ( ! empty($errors)) {
				$errorArray[$fieldName] = $errors;
			} else {
				
				$type = $formInputs[$fieldName]['type'];
				// skip useless data, if any
				if (in_array($type, array('captcha', 'button', 'submit'))) {
					continue;
				}
				$postValues[$fieldName] = $post->get($fieldName, null);
			}
		}
		
		$errorArray = $this->checkDependencyRequiredInputs($errorArray, $postValues, $formInputs);
		
		if (empty($errorArray)) {
			$this->sendMail($postValues, $formInputs);
		} else {
			// simplify error output for js: only first error label will be returned
			foreach($errorArray as $key => &$error) {
				if (isset($error[0]) && ! is_null($error[0])) {
					$error = $error[0];
				} else {
					unset($errorArray[$key]);
				}
			}
		}
		
		$response = array(
			'success' => (empty($errorArray) ? true : false),
			'errors' => $errorArray,
		);
		
		$this->getResponse()
				->output(json_encode($response));
	}
	
	/**
	 * Helper method/wrapper to send mails with posted data
	 * using Supra's Mailer
	 * 
	 * @param array $postValues
	 * @param array $formInputs
	 * @return boolean
	 */
	private function sendMail(array $postValues, array $formInputs) 
	{
		$response = new \Supra\Response\TwigResponse($this);
		$response->assign('postValues', $postValues);
		$response->assign('formInputs', $formInputs);
		$response->outputTemplate('email.html.twig');
		
		$userConfig = ObjectRepository::getIniConfigurationLoader($this);
		$receiverEmail = $userConfig->getValue('feedback', 'receiver_email', null);
		$senderEmail = $userConfig->getValue('feedback', 'sender_email', null);
		$emailSubject = $userConfig->getValue('feedback', 'subject', null);
				
		$mailer = ObjectRepository::getMailer($this);
		$message = \Swift_Message::newInstance($emailSubject, (string)$response, 'text/html', 'utf8')
			->setTo(array($receiverEmail))
			->setFrom( array($senderEmail) );
		
		$mailerResult = $mailer->send($message);
		
		if ($mailerResult == 0) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * Validates value for input field by given $id and validation 
	 * rules from $input definition
	 * 
	 * @param string $id
	 * @param array $input
	 * @return string
	 */
	private function checkIsInputValid($id, array $input)
	{
		$errors = array();
		$postInput = $this->getRequest()
				->getPost();
		
		$validation = &$input['validation'];
		
		if ( ! isset($validation['type'])) {
			$validation['type'] = 'any';
		}
		
		$postedValue = $postInput->get($id, null);
		
		if (isset($validation['required']) && $validation['required']) {
			if (empty($postedValue)) {
				$errors[] = 'required';
			}
		}
		
		if (isset($validation['minLength']) || isset($validation['maxLength'])) {
			$strlen = mb_strlen((string)$postedValue);
			
			if (isset($validation['minLength']) && $strlen < $validation['minLength']) {
				$errors[] = 'minLength';
			}
			
			if (isset($validation['maxLength']) && $strlen > $validation['maxLength']) {
				$errors[] = 'maxLength';
			}
		}
		
		$typeValidationFail = false;
		switch($validation['type']) {
			case 'email':
				try {
					if ( ! empty($postedValue)) {
						$postInput->getValid($id, AbstractType::EMAIL);
					}
				} catch (ValidationFailure $e) {
					$typeValidationFail = true;
				}
				break;
			
			case 'phone': 
				if (preg_match('/^([0-9\(\)\/\+ ]*)$/', $postedValue) == 0) {
					$typeValidationFail = true;
				}
				break;
				
			case 'captcha':
				$captcha = new GjensidigeCaptcha();
				$storedCaptchaHash = $captcha->getSessionStoredHash();
		
				$captcha = $postInput->get($id, null);
				$captcha = mb_strtoupper($captcha); // captcha uses only uppercase letters
				if (empty($captcha) || sha1($captcha) !== $storedCaptchaHash) {
					$typeValidationFail = true;
				}
				break;
				
		}
		
		if ($typeValidationFail) {
			$errors[] = 'invalid';
		}
		
		return $errors;
		
	}
	
	/**
	 * Tries to locate inputs, that have "required"-dependency from some another input field
	 * (real case example from "Gjensidige" project: email could be empty, if phone is provided
	 * and phone could be empty, if email is provided)
	 * 
	 * Return invalid inputs array, with unset required error value for inputs, that could be empty because
	 * some another dependent value is provided and valid
	 * 
	 * @param array $invalidInputs
	 * @param array $postValues
	 * @param array $formInputs
	 * @return array
	 */
	private function checkDependencyRequiredInputs(array $invalidInputs, array $postValues, array $formInputs) 
	{
		foreach($invalidInputs as $id => &$inputError) {
			
			$validation = $formInputs[$id]['validation'];
			if (isset($validation['requiredDependsOn']) 
					&& ! isset($invalidInputs[$validation['requiredDependsOn']])) {
				
				$key = array_search('required', $inputError);
				if ($key !== false && isset($postValues[$validation['requiredDependsOn']])) {
					unset($inputError[$key]);
				}
			}			
		}
		
		return $invalidInputs;
	}
	
}
