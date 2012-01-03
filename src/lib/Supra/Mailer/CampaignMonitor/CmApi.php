<?php

namespace Supra\Mailer\CampaignMonitor;

use Supra\Exception;


/**
 * Campaign Monitor API class
 */
class CmApi
{
	
	const WRAPPER_CLIENT='Campaigns';
	const WRAPPER_CAMPAIGN='Clients';
	const WRAPPER_GENERAL='Wrapper_Base';
	const WRAPPER_LISTS='Lists';
	const WRAPPER_SUBSCRIBER='Subscribers';
	
	private $wrapper = array();
	
	/**
	 * API key
	 * @var string
	 */
	protected $apiKey;
	
	/**
	 * Current client id
	 * @var string
	 */
	protected $currentClientId;
	
	/**
	 * Sets API key
	 * @param string $key 
	 */
	public function setApiKey($key){
		$this->apiKey = $key;
	}
	
	/**
	 * Returns API key
	 * @return string
	 */
	public function getApiKey() {
		return $this->apiKey;
	}
	
	/**
	 * Set current client ID
	 * @param string $clientId 
	 */
	public function setCurrentClientId($clientId)
	{
		$this->currentClientId = $clientId;
	}
	
	/**
	 * Returns current client ID
	 * @return string
	 */
	public function getCurrentClientId()
	{
		return $this->currentClientId;
	}

	
	/**
	 * Returns CM wrapper
	 * @param string $wrapperId
	 * @param string|null $resourceId
	 * @return CS_REST_General
	 */
	public function getWrapper($wrapperId, $resourceId = null){

		$object = null;
		
		if( isset ($this->wrapper[$wrapperId])) {
			
			return $this->wrapper[$wrapperId];
		}
		
		
		switch($wrapperId) {
			case self::WRAPPER_CAMPAIGN:{
				
				require_once SUPRA_LIBRARY_PATH . CampaignMonitor . 'csrest_campaigns.php';
				$object = new CS_REST_Campaigns($resourceId, $this->apiKey);
				
			}break;
			case self::WRAPPER_CLIENT:{
				
				require_once SUPRA_LIBRARY_PATH . CampaignMonitor . 'csrest_clients.php';
				$object = new CS_REST_Clients($resourceId, $this->apiKey);
				
			}break;
			case self::WRAPPER_GENERAL:{
				
				require_once SUPRA_LIBRARY_PATH . CampaignMonitor . 'csrest_general.php';
				$object = new CS_REST_General($resourceId, $this->apiKey);
				
			}break;
			case self::WRAPPER_LISTS:{
				
				require_once SUPRA_LIBRARY_PATH . CampaignMonitor . 'csrest_lists.php';
				$object = new CS_REST_Lists($resourceId, $this->apiKey);
				
			}break;
			case self::WRAPPER_SUBSCRIBER:{

				require_once SUPRA_LIBRARY_PATH . CampaignMonitor . 'csrest_subscribers.php';
				$object = new CS_REST_Subscribers($resourceId, $this->apiKey);
				
			}break;
			default:{
				throw new Exception\LogicException('Unknown wrapper id');
			}
		}

		$this->wrapper[$wrapperId] = $object;
		
		return $object;
		
	}
	
}
