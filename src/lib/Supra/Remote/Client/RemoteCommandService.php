<?php

namespace Supra\Remote\Client;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Supra\Console\Output\CommandOutputWithData;
use Symfony\Component\Console\Output\StreamOutput;
use Supra\ObjectRepository\ObjectRepository;

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
		if ($output instanceof CommandOutputWithData) {
			$proxyOutput = new ProxyOutput\ProxyOutputWithData($output);
		} else {
			$proxyOutput = new ProxyOutput\ProxyOutput($output);
		}

		$postData = array(
			self::POST_KEY_COMMAND_INPUT => serialize($input),
			self::POST_KEY_COMMAND_OUTPUT => serialize($proxyOutput),
		);

		return $postData;
	}

	/**
	 * @param string $remoteName
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	public function execute($remoteName, InputInterface $input, OutputInterface $output)
	{
		$apiEndpointUrl = $this->getApiEndpointUrl($remoteName);

		$postData = $this->getRemoteCommandPostData($input, $output);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $apiEndpointUrl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_USERAGENT, 'cURL/PHP');
		//curl_setopt($ch, CURLOPT_COOKIE, 'XDEBUG_SESSION=netbeans-xdebug');

		$rawResponse = curl_exec($ch);

		$remoteCommandResponse = unserialize($rawResponse);

		if (empty($remoteCommandResponse)) {
			throw new Exception\RuntimeException('Failed to un serialize remote command response.');
		}

		if ( ! $remoteCommandResponse instanceof RemoteCommandResponse) {
			throw new Exception\RuntimeException('Remote command response must be instance of RemoteCommandResponse class.');
		}

		$proxyOutput = $remoteCommandResponse->getProxyOutput();
		
		$proxyOutput->flushBufferToOutput($output);
		
		if($output instanceof CommandOutputWithData) {
			/* @var $proxyOutput ProxyOutput\ProxyOutputWithData */
			$output->setData($proxyOutput->getData());
		}

		if ($remoteCommandResponse->getSuccess()) {
			return $remoteCommandResponse->getResultCode();
		}

		$responseError = $remoteCommandResponse->getError();

		if ( ! empty($responseError)) {

			if ($responseError instanceof \Exception) {
				throw $responseError;
			} else {
				throw new Exception\RuntimeException('Remote command failed, error: ', $responseError);
			}
		} else {
			throw new Exception\RuntimeException('Remote command failed.');
		}
	}

}
