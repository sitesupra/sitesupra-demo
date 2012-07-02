<?php

namespace Supra\Cms\Dashboard;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Cms\Exception\CmsException;
use Supra\User\Entity\User;
use Supra\User\UserProviderAbstract;

class DasboardAbstractAction extends CmsAction
{
	/**
	 * @var User
	 */
	protected $currentUser;
	
	/**
	 * @var UserProviderAbstract
	 */
	protected $userProvider;
	
	
	public function __construct()
	{
		parent::__construct();
		
		$this->userProvider = ObjectRepository::getUserProvider($this);
		$this->currentUser = $this->userProvider->getSignedInUser();
		
		if( ! $this->currentUser instanceof User) {
			throw new CmsException(null, 'Failed to find current user');
		}
	}
	
}