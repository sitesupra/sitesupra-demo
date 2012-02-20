<?php

namespace Supra\Cms\ContentManager\Virtualfolder;

use Supra\Controller\SimpleController;
use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\Entity;
use Supra\Cms\Exception\CmsException;


class VirtualfolderAction extends PageManagerAction
{
	/**
	 * Action for delete virtual folder
	 */
	public function deleteAction(){
		
		$this->isPostRequest();
		$folder = $this->getPageLocalization()->getMaster();

		if( ! $folder instanceof Entity\GroupPage ) {
			throw new CmsException(null, "Not a virtualfolder object");
		}
		
		
		if ($folder->hasChildren()) {
			throw new CmsException(null, "Cannot remove virtualfolder with children");
		}
		
		$this->delete();
		$this->writeAuditLog('%item% deleted', $folder);
		$this->getResponse()->setResponseData(null);		
	}
	
	/**
	 * Action for rename virtual folder
	 */
	public function renameAction(){
		
		$this->isPostRequest();
		$folder = $this->getPageLocalization();
		
		if( ! $folder instanceof Entity\GroupLocalization ) {
			throw new CmsException(null, "Not a virtualfolder object");
		}
		
		$title = $this->getRequestParameter('title');
		$folder->setTitle($title);
		$this->entityManager->flush();
		$this->writeAuditLog('%item% renamed', $folder);		
		$this->getResponse()->setResponseData(null);
	}
	
}
