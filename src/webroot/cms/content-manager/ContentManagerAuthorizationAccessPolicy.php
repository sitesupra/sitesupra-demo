<?php

namespace Supra\Cms\ContentManager;

use Supra\Authorization\AccessPolicy\AuthorizationThreewayAccessPolicy;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity;
use Supra\User\Entity\Abstraction\User;

class ContentManagerAuthorizationAccessPolicy extends AuthorizationThreewayAccessPolicy {
	
	private $pr;
	private $lr;
	private $lm;
	
	function __construct() 
	{
		parent::__construct('pages', Entity\Abstraction\Entity::CN());
		
		$this->lm = ObjectRepository::getLocaleManager($this);

		$em = ObjectRepository::getEntityManager($this);
		$this->pr = $em->getRepository(Entity\Page::CN());
		$this->lr = $em->getRepository(Entity\PageLocalization::CN());

		
		// yes i know, this is rather dirty.
		$this->permission['subproperty']['localized'] = true;
	}
	
	protected function getItemPermission(User $user, $itemId, $permissions) 
	{
		$page = $this->pr->find($itemId);
		
		if( empty($page)) {
			$page = $this->lr->find($itemId);
		}
		
		$locale = '';
		
		
		if($page instanceof Entity\PageLocalization) {
			
			// if item was page loaclization, read locale value and 
			// fetch title directly ...	
			
			$locale = $page->getLocale();
			$title = $page->getTitle();
		}
		else { 
			
			// ... otherwise, if this is some master page, fetch current or first page localization 
			// and get title from that
			
			$localization = $page->getLocalization($this->lm->getCurrent()->getId());
			
			if (empty($localization)) {
				$localization = $page->getLocalizations()->first();
			}
			
			$title = $localization->getTitle();
		}
		
		$itemPermission = parent::getItemPermission($user, $itemId, $permissions);
		
		$itemPermission['locale'] = $locale;
		$itemPermission['title'] = $title;
		
		return $itemPermission;
	}
}
