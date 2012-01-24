<?php

namespace Supra\BannerMachine;

use Supra\BannerMachine\Entity\Banner;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\ControllerAbstraction;
use Supra\Response\HttpResponse;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Uri\NullPath;
use Supra\Controller\Exception\ResourceNotFoundException;
use Supra\BannerMachine\Exception\BannerMachineException;

class BannerMachineRedirector extends ControllerAbstraction
{
	const REQUEST_KEY_BANNER_ID = 'b';
	const REQUEST_KEY_EXTRA = 'e';
	const REQUEST_KEY_PAGE_REF = 'r';

	public function execute()
	{
		$log = ObjectRepository::getLogger($this);
		
		try {

			$request = $this->getRequest();

			$bannerId = $request->getParameter(self::REQUEST_KEY_BANNER_ID);

			$bannerProvider = ObjectRepository::getBannerProvider($this);

			/** @var $banner Banner */
			$banner = $bannerProvider->getBanner($bannerId);

			$bannerProvider->increaseBannerClickCounter($banner);

			$response = $this->getResponse();
			if ($response instanceof HttpResponse) {

				if ($banner->getTargetType() == Banner::TARGET_TYPE_EXTERNAL) {

					$response->redirect($banner->getExternalTarget());
				} else {

					$em = ObjectRepository::getEntityManager($this);
					$repo = $em->getRepository(PageLocalization::CN());

					/* @var $pageLocalization PageLocalization */
					$pageLocalization = $repo->find($banner->getInternalTarget());


					if ( ! empty($pageLocalization)) {

						$path = $pageLocalization->getPath();

						if ($path instanceof NullPath) {
							throw new ResourceNotFoundException('Banner target not available.');
						} else {
							$response->redirect($path);
						}
					}
				}
			}
		} catch (BannerMachineException $e) {
			$log->info($e);
		}
	}

}
