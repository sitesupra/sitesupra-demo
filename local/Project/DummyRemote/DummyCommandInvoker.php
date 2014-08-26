<?php

namespace Project\DummyRemote;

use Supra\Controller\SimpleController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Remote\Command\CommandOutputWithData;
use Supra\Remote\Client\RemoteCommandService;
use Symfony\Component\Console\Input\ArrayInput;
use Supra\Console\Output\ArrayOutputWithData;

class DummyCommandInvoker extends SimpleController
{

	public function execute()
	{
		$response = $this->getResponse();

		$remoteCommandService = new RemoteCommandService();
		//$remoteCommandService = ObjectRepository::getRemoteCommandService($this);

		$input = new ArrayInput(array(
					'command' => 'su:utility:get_all_users'
				));
		
		$output = new ArrayOutputWithData();

		$success = $remoteCommandService->execute('supra7.krists.vig', $input, $output);

		$content = var_export(array(
			'SUCCESS' => $success,
			'DATA' => $output->getData(),
			'OUTPUT' => $output->getOutput()
		), true);
		
 		$response->output('<pre>' . $content);
	}

}
