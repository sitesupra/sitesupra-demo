<?php

namespace Project\CmsRemoteLogin;

use Supra\Controller\SimpleController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Remote\Command\CommandOutputWithData;
use Supra\Remote\Client\RemoteCommandService;
use Symfony\Component\Console\Input\ArrayInput;
use Supra\Console\Output\ArrayOutputWithData;
use Supra\User\Entity as UserEntity;
use SupraPortal\SiteUser\Entity as SiteUserEntity;

class Controller extends SimpleController//\Supra\Controller\ControllerAbstraction
{
	
	/**
	 * @var \Supra\User\UserProvider;
	 */
	protected $userProvider;
	
	protected $remoteUrl;

	public function execute()
	{

		$siteId = ObjectRepository::getIniConfigurationLoader($this)->getValue('system', 'id');		
		
		$this->userProvider = ObjectRepository::getUserProvider($this);
				
		$remoteCommandService = new RemoteCommandService();
		
		$input = new ArrayInput(array(
					'command' => 'su:utility:get_user_by_token',
					'token' => $this->getRequest()->getParameter('token'),
					'site'	=> $siteId,
				));

		$output = new ArrayOutputWithData();
		$commandResult = $remoteCommandService->execute('portal', $input, $output);

		$user = $output->getData();

		if ($user instanceof UserEntity\User) {

			$this->processRemoteUserData($user);
			
		} else {
			
			$this->badCredential();
			
		}
	}

	protected function processRemoteUserData(\Supra\User\Entity\User $user)
	{
		$signedInUser = $this->userProvider->getSignedInUser();
			
		//If user already signed in
		if($signedInUser instanceof \Supra\User\Entity\User) {
			
			//Sign out old user and sign in new
			if($signedInUser->getId() != $user->getId()) {
				$this->userProvider->signOut();
				$this->userProvider->signIn($user);
			}
		} else {
			//sign in user
			$this->userProvider->signIn($user);
		}
		
		$this->getResponse()->redirect('/cms');
		
	}

	protected function badCredential()
	{
		$this->getResponse()->redirect('/cms');
	}
	
}
