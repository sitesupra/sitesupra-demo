<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\Common\EventSubscriber;
use Supra\Controller\Pages\Event\CmsPageEventArgs;
use Supra\ObjectRepository\ObjectRepository;

class MakeLocalizationPreviewListener
{
	/**
	 * @param CmsPageEventArgs $args
	 */
	public function postPageChange(CmsPageEventArgs $args)
	{
		$ini = ObjectRepository::getIniConfigurationLoader($this);
		$systemInfo = ObjectRepository::getSystemInfo($this);
		
		$gearmanServerHost = $ini->getValue('gearman', 'host');
		$siteId = $ini->getValue('system', 'id');
		

		if ($args->localization instanceof \Supra\Controller\Pages\Entity\PageLocalization) {
			$localizationType = 'p';
		} else if ($args->localization instanceof \Supra\Controller\Pages\Entity\TemplateLocalization) {
			$localizationType = 't';
		}

		$jobData = array(
			'siteHost' => $systemInfo->getWebserverHostAndPort(),
			'siteId' => $siteId,
			'localizationType' => $localizationType,
			'localizationId' => $args->localization->getId(),
			'revisionId' => $args->localization->getRevisionId(),
		);

//		$gearmanClient = new \GearmanClient();
//		$gearmanClient->addServers($gearmanServerHost);
//		$gearmanClient->doBackground('makeLocalizationPreview', json_encode($jobData));
	}

}
