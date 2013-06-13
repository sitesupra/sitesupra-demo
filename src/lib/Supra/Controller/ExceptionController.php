<?php

namespace Supra\Controller;

use Supra\Response\HttpResponse;
use Supra\Authorization\Exception\AccessDeniedException;
use Supra\Controller\Exception\MethodNotAllowedException;
use Supra\ObjectRepository\ObjectRepository;

/**
 * ExceptionController
 */
class ExceptionController extends ControllerAbstraction
{

	/**
	 * @var Exception
	 */
	private $exception;

	/**
	 * @return Exception
	 */
	public function getException()
	{
		return $this->exception;
	}

	/**
	 * @param Exception $exception
	 */
	public function setException(\Exception $exception)
	{
		$this->exception = $exception;
	}

	/**
	 * Ouput exception string
	 */
	public function execute()
	{
		$response = $this->getResponse();

		// HTTP response specifics
		if ($response instanceof HttpResponse) {
			$response->header("Content-Type", "text/plain");

			if ($this->exception instanceof Exception\ResourceNotFoundException) {
				$response->setCode(404);
                $errorPage = file_get_contents('404.html');
                $response->header('Content-Type', 'text/html', true);
				$response->output($errorPage);
			} else if ($this->exception instanceof MethodNotAllowedException) {
				$response->setCode(405);
				$response->output("405 METHOD NOT ALLOWED\n");
			} else if ($this->exception instanceof AccessDeniedException) {
				$response->setCode(403);
				$response->output("403 FORBIDDEN\n");
			} else {
				$response->setCode(500);
                $errorPage = file_get_contents('500.html');
                $response->header('Content-Type', 'text/html', true);
				$response->output($errorPage);

				$iniConfiguration = ObjectRepository::getIniConfigurationLoader($this);

				if ($iniConfiguration->getValue('system', 'email_exceptions', false) == true) {

					$this->sendExceptionReportToEmail();
				}
			}
		}

//		$response->output("\n" . $this->exception->__toString());
	}

	protected function sendExceptionReportToEmail()
	{
		$mailer = ObjectRepository::getMailer($this);
		$systemInfo = ObjectRepository::getSystemInfo($this);
		$userProvider = ObjectRepository::getUserProvider('#cms');

		$exceptionIdentifier = md5((string) $this->exception);

		$message = new \Swift_Message('Caught exception, #' . $exceptionIdentifier);

		$bodyParts = array();

		$bodyParts['Trace'] = $this->exception->getTraceAsString();

		$systemInfoData = array();
		foreach ($systemInfo->getAsArray() as $name => $value) {
			$systemInfoData[] = $name . ': ' . $value;
		}

		$bodyParts['System info'] = join("\n", $systemInfoData);

		$currentUser = $userProvider->getSignedInUser(false);

		if ( ! empty($currentUser)) {
			$bodyParts['User login'] = $currentUser->getLogin();
		} else {
			$bodyParts['User login'] = 'N/A';
		}

		$body = array();
		foreach ($bodyParts as $name => $bodyPart) {
			$body[] = $name . "\n" . '------------------------------------' . "\n" . $bodyPart . "\n";
		}
		$body = join("\n", $body);

		$message->setBody($body);

		$iniConfiguration = ObjectRepository::getIniConfigurationLoader($this);
		
		$toAddress = $iniConfiguration->getValue('system', 'email_exceptions_to');
		$message->setTo($toAddress);

		$fromAddress = $iniConfiguration->getValue('system', 'email_exceptions_from', false);
		if (empty($fromAddress)) {
			$fromAddress = $iniConfiguration->getValue('mail', 'default_email');
		}
		$message->setFrom($fromAddress);

		$mailer->send($message);
	}

}