<?php

namespace Supra\Cms\Help;

use Supra\Cms\Exception\CmsException;

/**
 *
 */
class HelpController extends \Supra\Cms\CmsAction
{
	/**
	 * 
	 */
	public function tipsSaveAction()
	{
		$tipId = $this->getRequest()
				->getPostValue('id');
		
		if (empty($tipId)) {
			throw new CmsException(null, 'No tip id found in post request');
		}
		
		$provider = \Supra\ObjectRepository\ObjectRepository::getUserProvider($this);
		$user = $provider->getSignedInUser();
		
		if (empty($user)) {
			throw new CmsException(null, 'Nobody is logged in');
		}
		
		$id = $this->normalizeTipId($tipId);
		
		$userPreferences = $provider->getUserPreferences($user);
		
		$closedTipIds = array();
		if (isset($userPreferences['closed_tips'])) {
			 $closedTipIds = $userPreferences['closed_tips'];
		}
		
		if ( ! in_array($id, $closedTipIds)) {
			array_push($closedTipIds, $id);
			$provider->setUserPreference($user, 'closed_tips', $closedTipIds);
		}
		
		$this->getResponse()
				->setResponseData(true);
	}
	
	/**
	 * @param string $tipId
	 * @return string
	 */
	private function normalizeTipId($tipId)
	{
		$id = trim(substr($tipId, 0, 20));
		
		return $id;
	}
	
}
