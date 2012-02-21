<?php

namespace Supra\Remote\Server;

use Supra\Controller\ControllerAbstraction;
use Supra\Response\JsonResponse;
use Supra\Remote\Command;
use Supra\Remote\Exception\RemoteException;
use Supra\ObjectRepository\ObjectRepository;
use Supra\AuditLog\Writer\AuditLogWriter;
use Supra\Log\Log;
use Supra\User\UserProvider;
use Supra\User\SystemUser;
use Supra\Remote\Command\RemoteCommandAbstraction;
use Supra\Request\RequestInterface;
use Supra\Request\HttpRequest;
use Supra\Remote\Client\RemoteCommandService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Remote\Client\RemoteCommandResponse;
use Supra\Remote\Client\ProxyOutput\ProxyOutput;

class RemoteCommandController extends ControllerAbstraction
{

	/**
	 * @var AuditLogWriter
	 */
	protected $auditLogger;

	/**
	 * @var Log
	 */
	protected $log;

	/**
	 * @return AuditLogWriter
	 */
	public function getAuditLogger()
	{
		if (empty($this->auditLogger)) {
			$this->auditLogger = ObjectRepository::getAuditLogger($this);
		}

		return $this->auditLogger;
	}

	/**
	 * @param AuditLogWriter $auditLogger 
	 */
	public function setAuditLogger(AuditLogWriter $auditLogger)
	{
		$this->auditLogger = $auditLogger;
	}

	/**
	 * @return Log
	 */
	public function getLog()
	{
		if (empty($this->log)) {
			$this->log = ObjectRepository::getLogger($this);
		}

		return $this->log;
	}

	/**
	 * @param type $log 
	 */
	public function setLog($log)
	{
		$this->log = $log;
	}

	public function getCommandClasses()
	{
		return array(
			'Project\DummyRemote\DummyCommandOne',
		);
	}

	/**
	 * @return SystemUser
	 */
	protected function getSystemUser()
	{
		$userProvider = new UserProvider();

		$systemUser = $userProvider->findUserByLogin(SystemUser::LOGIN);

		return $systemUser;
	}

	public function execute()
	{
		$response = $this->getResponse();

		/* @var $response JsonResponse */

		$request = $this->getRequest();
		$post = $request->getPost();

		if ( ! $post->has(RemoteCommandService::POST_KEY_COMMAND_INPUT)) {
			throw new Exception\RuntimeException('No command input data in POST.');
		}

		/* @var $input InputInterface */
		$input = unserialize(base64_decode($post->get(RemoteCommandService::POST_KEY_COMMAND_INPUT)));

		if (empty($input)) {
			throw new Exception\RuntimeException('Failed to unserialize command input.');
		}

		if ( ! $input instanceof InputInterface) {
			throw new Exception\RuntimeException('Command input must instance of class implementing InputInterface.');
		}

		if ( ! $post->has(RemoteCommandService::POST_KEY_COMMAND_OUTPUT)) {
			throw new Exception\RuntimeException('No command output data in POST.');
		}

		/* @var $output OutputInterface */
		$output = unserialize(base64_decode($post->get(RemoteCommandService::POST_KEY_COMMAND_OUTPUT)));

		if (empty($output)) {
			throw new Exception\RuntimeException('Failed to unserialize command output.');
		}

		if ( ! $output instanceof ProxyOutput) {
			throw new Exception\RuntimeException('Command output must instance of class ProxyOutput.');
		}

		$remoteCommandResponse = new RemoteCommandResponse();

		try {

			$application = \Supra\Console\Application::getInstance();
			$application->addCommandClasses($this->getCommandClasses());
			$application->setAutoExit(false);

			$resultCode = $application->run($input, $output);

			$remoteCommandResponse->setResultCode($resultCode);
			$remoteCommandResponse->setProxyOutput($output);
			$remoteCommandResponse->setSuccess(true);
		} catch (\Exception $e) {
			
			$remoteCommandResponse->setError($e);
			$remoteCommandResponse->setSuccess(false);
		}

		$response->output(base64_encode(serialize($remoteCommandResponse)));

		$auditMessage = 'Command "' . $input->getFirstArgument() . '" executed.';
		$auditUser = $this->getSystemUser();
		$auditData = array(
			'input' => $input,
			'output' => $output
		);

		$this->getAuditLogger()
				->info('Supra\Remote\Server', $auditMessage, $auditUser, $auditData);
	}

}
