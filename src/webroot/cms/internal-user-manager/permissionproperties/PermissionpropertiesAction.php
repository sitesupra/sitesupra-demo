<?php

namespace Supra\Cms\InternalUserManager\Permissionproperties;

use Supra\Cms\InternalUserManager\InternalUserManagerAbstractAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequest;
use Supra\Controller\Pages\Entity as PageEntity;
use Supra\FileStorage\Entity as FileEntity;
use Supra\Cms\CmsApplicationConfiguration;
use Supra\Authorization\AccessPolicy\AuthorizationThreewayAccessPolicy;
use Supra\Locale\LocaleManager;
use Supra\Cms\ApplicationConfiguration;
use Supra\Authorization\Exception\ConfigurationException as AuthorizationConfigurationException;

class PermissionpropertiesAction extends InternalUserManagerAbstractAction
{
	public function datalistAction() {
		
		switch($this->getRequestParameter('application_id')) {
			
			case 'media-library': {
				
				$response = $this->getMediaLibraryTreeData();
				
			} break;
			
			case 'content': {
				
				$response = $this->getContentTreeData();
				
			} break;
		}
		
		$this->getResponse()->setResponseData($response);
	}
	
	private function getContentTreeData() {
		
		$pages = array();
		
		$localeId = $this->getRequest()->getQueryValue('locale');

		$em = ObjectRepository::getEntityManager($this);

		$response = array();

		$pageRepository = $em->getRepository(PageRequest::PAGE_ENTITY);

		/* @var $pageRepository \Supra\Controller\Pages\Repository\PageRepository */
		$rootNodes = $pageRepository->getRootNodes();

		foreach ($rootNodes as $rootNode) {
			$tree = $this->buildContentTreeArray($rootNode, $localeId);
			// TODO: hardcoded
			$tree['icon'] = 'home';
			$tree['title'] = '[' . $localeId . '] Home';

			$response[] = $tree;
		}
		
		return $response;
		
	}
	
	private function buildContentTreeArray(PageEntity\Page $page, $localeId)
	{
		$itemId = null;
		
		if( empty($localeId)) {
			
			$itemId = $page->getId();
			
			$lm = ObjectRepository::getLocaleManager($this);
			$localization = $page->getLocalization($lm->getCurrent()->getId());
			
			if (empty($localization)) {
				$allLocalizations = $page->getLocalizations();
				$localization = $allLocalizations->first();
			}
		}
		else {
		
			$localization = $page->getLocalization($localeId);

			if (empty($localization)) {
				return;
			}
			
			$itemId = $localization->getId();
		}

		if (empty($localization)) {
			return null;
		}

		\Log::debug('TREE HAS ITEM: ', $itemId);
		
		$array = array(
			'id' => $itemId,
			'title' => '[' . $localeId . '] ' . $localization->getTitle(),
			// TODO: hardcoded
			'icon' => 'page',
			'preview' => '/cms/lib/supra/img/sitemap/preview/page-1.jpg'
		);

		$array['children'] = array();

		foreach ($page->getChildren() as $child) {
			
			$childArray = $this->buildContentTreeArray($child, $localeId);

			if ( ! empty($childArray)) {
				$array['children'][] = $childArray;
			}
		}

		if (count($array['children']) == 0) {
			unset($array['children']);
		} else {
			// TODO: hardcoded
			$array['icon'] = 'folder';
		}

		return $array;
	}

	private function getMediaLibraryTreeData() 
	{
		$ml = ObjectRepository::getFileStorage($this);
		
		$rootNodes = array();
		
		// FIXME: store the classname as constant somewhere?
		/* @var $repo FileRepository */
		$repo = $this->entityManager->getRepository('Supra\FileStorage\Entity\Abstraction\File');

		$output = array();

		$rootNodes = $repo->getRootNodes();

		foreach ($rootNodes as $rootNode) {
			$response[] = $this->buildMediaLibraryTreeArray($rootNode);
		}

		return $response;
	}
	
	private function buildMediaLibraryTreeArray(FileEntity\Abstraction\File $file) 
	{ 
		if( ! ($file instanceof FileEntity\Folder)) {
			return array();
		}
		
		$array = array(
			'id' => $file->getId(),
			'title' => $file->getFileName(),
			'template' => null,
			'path' => $file->getPath(),
			'icon' => 'folder'
		);

		$array['children'] = array();

		foreach ($file->getChildren() as $child) {
			
			$childArray = $this->buildMediaLibraryTreeArray($child);

			if ( ! empty($childArray)) {
				$array['children'][] = $childArray;
			}
		}

		if (count($array['children']) == 0) {
			unset($array['children']);
		} 
		
		return $array;
	}
	
	public function saveAction() 
	{
		/* $internalUserManagerAppConfig ApplicationConfiguration */
		$internalUserManagerAppConfig = ObjectRepository::getApplicationConfiguration($this);
		
		$cmsAppConfigs = CmsApplicationConfiguration::getInstance();

		/* $appConfig ApplicationConfiguration */
		$appConfig = $cmsAppConfigs->getConfiguration($this->getRequest()->getPostValue('application_id'));
		
		// disallow application access permissions for this (Internal User Manager) 
		// application. This is a safeguard.
		if($appConfig->id == $internalUserManagerAppConfig->id) {
			throw new AuthorizationConfigurationException('Application access permission modification for Internal User Manager is forbiden.');
		}
		
		$user = $this->userProvider->findUserById($this->getRequest()->getPostValue('user_id'));
		
		// if admin added / removed / clicked in permission exceptions list, 
		// process that, ignore "allow" property
		if( 
				( ! is_null($this->getRequest()->getPostValue('list'))) &&
				($appConfig->authorizationAccessPolicy instanceof AuthorizationThreewayAccessPolicy)
		){
			
			$itemUpdate = $this->getRequest()->getPostValue('list');
			
			$itemId = $itemUpdate['id'];
			$itemPermissions = $itemUpdate['value'];

			$appConfig->authorizationAccessPolicy->setItemPermissions($user, $itemId, $itemPermissions);
		}
		else if($this->getRequest()->getPostValue('property') == 'allow') {
			
			// .. or if admin changed application access permission, do that.
		
			$appConfig->authorizationAccessPolicy->setAccessPermission(
					$user, 
					$this->getRequest()->getPostValue('value')
			);
		}
		else { 
			// ... bail on everything else
			throw new AuthorizationConfigurationException('Do not know what to do with property "' . $this->getRequest()->getPostValue('property') . '"');
		}
	}
}
