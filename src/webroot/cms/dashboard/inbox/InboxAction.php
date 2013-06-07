<?php

namespace Supra\Cms\Dashboard\Inbox;

use Supra\Cms\Dashboard\DasboardAbstractAction;
use Supra\User\Notification\UserNotificationService;
use Supra\Remote\Client\RemoteCommandService;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Console\Output\ArrayOutputWithData;
use Symfony\Component\Console\Input\ArrayInput;
use Supra\User\Entity\UserSiteNotification;


class InboxAction extends DasboardAbstractAction
{
	
	/**
	 * @var RemoteCommandService
	 */
	protected $remoteCommandService;

	/**
	 * @var string
	 */
	protected $remoteApiEndpointId = 'portal';

    
	/**
	 * @return string
	 */    
	public function getRemoteApiEndpointId()
	{
		return $this->remoteApiEndpointId;
	}

	/**
	 * @return RemoteCommandService
	 */    
	public function getRemoteCommandService()
	{
		if (empty($this->remoteCommandService)) {
			$this->remoteCommandService = new RemoteCommandService();
		}
		return $this->remoteCommandService;
	}
    
    
	public function inboxAction()
	{
		
		$notificationService = new UserNotificationService();
		$userNotifications = $notificationService->getUserNotifications($this->currentUser, null);
		
		$response = array();
		
		foreach($userNotifications as $notification) {
			/* @var $notification \Supra\User\Entity\UserNotification */
			$response[] = array(
				'title' => $notification->getMessage(),
				'buy' => false,
				'new' => ( ! $notification->getIsRead()),
			);
		}
        
        
        $system = ObjectRepository::getSystemInfo($this);
        $siteId = $system->getSiteId();        
        $userId = $this->getUser()->getId();
        
		$commandParameters = array(
            'command' => 'su:portal:get_user_site_notifications',
			'user' => $userId,
            'site' => $siteId,
		);

		$commandResult = $this->executeSupraPortalCommand($commandParameters);
        $data = $commandResult->getData();
        foreach($data['data'] as $item) {
            if ($item instanceof UserSiteNotification) {
                $response[] = array(
                    'title' => $item->getMessage(),
                    'buy' => false,
                    'new' => ( ! $item->getIsRead()),
                );
            }
        }

		$this->getResponse()
				->setResponseData($response);	
	}
    
    
    
    
    public function executeSupraPortalCommand($parameters)
    {
    
        $remoteApiEndpoint = $this->getRemoteApiEndpointId();
        $remoteCommandService = $this->getRemoteCommandService();
        
		$output = new ArrayOutputWithData();
		$input = new ArrayInput($parameters);
        
        
        $remoteCommandService->execute($remoteApiEndpoint, $input, $output);
        
        return $output;
    }
	
}