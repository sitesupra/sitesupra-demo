<?php

namespace Supra\Cms\ContentManager\Helper;

use Supra\Cms\ContentManager\PageManagerAction;

class HelperAction extends PageManagerAction
{
	
	public function removeLocalizationAction()
	{
		
		$localizationId = $this->getRequestParameter('id');
		
		$draftEm = $this->entityManager;
		
		$localization = $draftEm->find(\Supra\Controller\Pages\Entity\Abstraction\Localization::CN(), $localizationId);
		if ($localization instanceof \Supra\Controller\Pages\Entity\Abstraction\Localization) {
			$draftEm->remove($localization);
			$draftEm->flush();
			
			$publicEm = $this->getPublicEntityManager();
			$localization = $publicEm->find(\Supra\Controller\Pages\Entity\Abstraction\Localization::CN(), $localizationId);
			
			$publicEm->remove($localization);
			$publicEm->flush();
		} else {
			$this->getResponse()->setResponseData('No localization found!');
		}
	}
	
	
}
