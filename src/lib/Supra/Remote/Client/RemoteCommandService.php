<?php

namespace Supra\Remote\Client;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Supra\Console\Output\CommandOutputWithData;
use Symfony\Component\Console\Output\StreamOutput;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Log\Log;
use Supra\AuditLog\Writer\AuditLogWriter;
use Supra\User\Entity\User;
use Supra\User\SystemUser;

class RemoteCommandService
{
	const POST_KEY_COMMAND_INPUT = 'commandInput';
	const POST_KEY_COMMAND_OUTPUT = 'commandOutput';

	const RESPONSE_KEY_COMMAND_RESULT_CODE = 'commandResultCode';
	const RESPONSE_KEY_COMMAND_OUTPUT = 'commandOutput';
	const RESPONSE_KEY_COMMAND_SUCCESS = 'commandSuccess';
	const RESPONSE_KEY_ERROR = 'error';

	const INI_SECTION_NAME = 'remote_api_endpoints';

	/**
	 * @var Log
	 */
	protected $log;

	/**
	 * @var AuditLogWriter
	 */
	protected $auditLog;

	/**
	 * @var User
	 */
	protected $user;

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
	 * @param Log $log 
	 */
	public function setLog(Log $log)
	{
		$this->log = $log;
	}

	/**
	 * @return AuditLogWriter
	 */
	public function getAuditLog()
	{
		if (empty($this->auditLog)) {
			$this->auditLog = ObjectRepository::getAuditLogger($this);
		}

		return $this->auditLog;
	}

	/**
	 * @param AuditLogWriter $auditLog 
	 */
	public function setAuditLog(AuditLogWriter $auditLog)
	{
		$this->auditLog = $auditLog;
	}

	/**
	 * @return User
	 */
	public function getUser()
	{
		if (empty($this->user)) {

			$up = new \Supra\User\UserProvider();

			$this->user = $up->findUserByLogin(SystemUser::LOGIN);
		}

		return $this->user;
	}

	/**
	 * @param User $user 
	 */
	public function setUser(User $user)
	{
		$this->user = $user;
	}

	/**
	 * @param string $remoteName 
	 * @return string
	 */
	protected function getApiEndpointUrl($remoteName)
	{
		$iniConfigLoader = ObjectRepository::getIniConfigurationLoader($this);

		$apiEndpointUrl = $iniConfigLoader->getValue(self::INI_SECTION_NAME, $remoteName, null);

		if (empty($apiEndpointUrl)) {
			throw new Exception\RuntimeException('Api endpoint URL not defined for remote "' . $remoteName . '".');
		}

		return $apiEndpointUrl;
	}

	protected function getRemoteCommandPostData(InputInterface $input, OutputInterface $output)
	{
		$proxyOutput = null;

		if ($output instanceof CommandOutputWithData) {
			$proxyOutput = new ProxyOutput\ProxyOutputWithData($output);
		} else {
			$proxyOutput = new ProxyOutput\ProxyOutput($output);
		}

		$postData = array(
			self::POST_KEY_COMMAND_INPUT => base64_encode(serialize($input)),
			self::POST_KEY_COMMAND_OUTPUT => base64_encode(serialize($proxyOutput))
		);

		return $postData;
	}

	/**
	 * @param string $endpointUrl
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return mixed
	 */
	public function executeWithUrl($endpointUrl, InputInterface $input, OutputInterface $output)
	{
		$postData = $this->getRemoteCommandPostData($input, $output);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $endpointUrl);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 20);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'cURL/PHP');
		//curl_setopt($ch, CURLOPT_COOKIE, 'XDEBUG_SESSION=netbeans-xdebug');

		$rawResponse = curl_exec($ch);

		$httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (empty($rawResponse)) {
			throw $this->makeExecuteRuntimeException('Remote command timed out or failed catastrophically. URL: ' . $endpointUrl . ', Response code: ' . $httpResponseCode, $input, $output);
		}

		$remoteCommandResponse = unserialize(base64_decode($rawResponse));

		if (empty($remoteCommandResponse)) {
			throw $this->makeExecuteRuntimeException('Failed to un serialize remote command response. URL: ' . $endpointUrl . ', Response code: ' . $httpResponseCode, $input, $output);
		}

		if ( ! $remoteCommandResponse instanceof RemoteCommandResponse) {
			throw $this->makeExecuteRuntimeException('Remote command response must be instance of RemoteCommandResponse class.', $input, $output);
		}

		$proxyOutput = $remoteCommandResponse->getProxyOutput();

		$proxyOutput->unproxy($output);

		if ($remoteCommandResponse->getSuccess()) {
			return $remoteCommandResponse->getResultCode();
		}

		$responseError = $remoteCommandResponse->getError();

		if ( ! empty($responseError)) {

			if ($responseError instanceof \Exception) {
				throw $responseError;
			} else {
				throw $this->makeExecuteRuntimeException('Remote command failed, error: ' . $responseError . ' URL: ' . $endpointUrl . ', Response code: ' . $httpResponseCode, $input, $output);
			}
		} else {
			throw $this->makeExecuteRuntimeException('Remote command failed. URL: ' . $endpointUrl . ', Response code: ' . $httpResponseCode, $input, $output);
		}
	}

	/**
	 * @param string $remoteName
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return mixed
	 */
	public function execute($remoteName, InputInterface $input, OutputInterface $output)
	{
		$endpointUrl = $this->getApiEndpointUrl($remoteName);

		return $this->executeWithUrl($endpointUrl, $input, $output);
	}

	/**
	 * @param string $level
	 * @param string $message
	 * @param array $auditData 
	 */
	protected function writeToAuditLog($level, $message, $auditData)
	{
		$auditLog = $this->getAuditLog();

		$user = $this->getUser();

		$auditLog->write($level, 'Supra\Remote', $message, $user, $auditData);
	}

	/**
	 * @param string $message
	 * @param array $auditData 
	 */
	protected function writeErrorToAuditLog($message, $auditData)
	{
		$this->writeToAuditLog('error', $message, $auditData);
	}

	/**
	 * @param string $message
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return Exception\RuntimeException 
	 */
	protected function makeExecuteRuntimeException($message, InputInterface $input, OutputInterface $output)
	{
		$auditData = array(
			'input' => $input,
			'output' => $output
		);

		$this->writeToAuditLog('error', $message, $auditData);

		return new Exception\RuntimeException($message);
	}

}
