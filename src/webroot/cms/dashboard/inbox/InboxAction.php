<?php

namespace Supra\Cms\Dashboard\Inbox;

use Supra\Cms\Dashboard\DasboardAbstractAction;
use Supra\User\Notification\UserNotificationService;
use Supra\Remote\Client\RemoteCommandService;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Console\Output\ArrayOutputWithData;
use Symfony\Component\Console\Input\ArrayInput;
use Supra\Controller\Pages\Entity\Blog\BlogApplicationComment;
use Supra\User\Entity\User;
use Supra\Translation\Translator;


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
        $system = ObjectRepository::getSystemInfo($this);
        $siteId = $system->getSiteId();
        $user = $this->getUser();
        $userId = $user->getId();
        
		$commandParameters = array(
            'command' => 'su:portal:get_user_site_statuses',
			'user' => $userId,
            'site' => $siteId,
		);

		$commandResult = $this->executeSupraPortalCommand($commandParameters);
        
        $translator = $this->getTranslator();

        $data = $commandResult->getData();
        if ($translator instanceof Translator) {
            if ($data['data']) {
                foreach($data['data'] as &$item) {
                    if ($item['valid_for']) {
                        $item['message'] = $translator->trans($item['message_code'], array('%count%' => $item['valid_for']), 'messages', 'en');
                    } else {
                        $item['message'] = $translator->trans($item['message_code'], array(), 'messages', 'en');
                    }
                }
            }    
        } else {
            $log = $this->getLog();
            $log->warn('Could not load Symfony\Component\Translation\Translator, unable to translate site statuses.');
        }
        

		$this->getResponse()
				->setResponseData($data['data']);	
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
    
    
	/**
	 * @return \Symfony\Component\Translation\Translator
	 */
	protected function getTranslator()
	{
		$translator = ObjectRepository::getObject($this, 'Symfony\Component\Translation\Translator');
		return $translator;
	}
}