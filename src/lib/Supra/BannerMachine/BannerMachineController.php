<?php

namespace Supra\BannerMachine;

use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\BannerType\BannerTypeAbstraction;
use Supra\Controller\Pages\Request\PageRequestView;
use Supra\BannerMachine\Exception\BannerNotFoundException;
use Supra\BannerMachine\Exception\RuntimeException as BannerMachineRuntimeException;

class BannerMachineController extends BlockController
{
	const PROPERTY_NAME_BANNER_TYPE = 'bannerType';
	const PROPERTY_NAME_APPEND_TO_URL = 'appendToTargetUrl';

	/**
	 * @var BannerProvider
	 */
	protected $bannerProvider;

	/**
	 * @return BannerProvider
	 */
	public function getBannerProvider()
	{
		if (empty($this->bannerProvider)) {
			$this->bannerProvider = ObjectRepository::getBannerProvider($this);
		}

		return $this->bannerProvider;
	}

	/**
	 * @return BannerTypeAbstraction
	 */
	private function getBannerType()
	{
		$bannerTypeId = $this->getPropertyValue(self::PROPERTY_NAME_BANNER_TYPE);

		return $this->getBannerProvider()
						->getType($bannerTypeId);
	}

	public function execute()
	{
		$request = $this->getRequest();

		if ($request instanceof PageRequestView) {
			$this->exposeBanner();
		}
		else {
			$this->editBanner();
		}
	}

	protected function exposeBanner()
	{
		$response = $this->getResponse();

		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$bannerContent = '';

		try {

			$bannerType = $this->getBannerType();
			
			$pageLocalization = $this->getRequest()->getPageLocalization();
			$localeId = $pageLocalization->getLocale();
			$lm = ObjectRepository::getLocaleManager($this);
			$locale = $lm->getLocale($localeId);

			$banner = $bannerProvider->getRandomBanner($bannerType, $locale);
			
			$bannerProvider->increaseBannerExposureCounter($banner);

			$bannerContent = $banner->getExposureModeContent($this);
		}
		catch (BannerNotFoundException $e) {

			$bannerContent = '<h1>BANNER NOT FOUND</h1>';
		}

		$response->assign('bannerContent', $bannerContent);
		$response->outputTemplate('banner-fe.html.twig');
	}

	protected function editBanner()
	{
		$response = $this->getResponse();

		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$bannerContent = '';

		try {

			$bannerType = $this->getBannerType();

			$pageLocalization = $this->getRequest()->getPageLocalization();
			$localeId = $pageLocalization->getLocale();
			$lm = ObjectRepository::getLocaleManager($this);
			$locale = $lm->getLocale($localeId);

			$banner = $bannerProvider->getRandomBanner($bannerType, $locale);

			$bannerContent = $banner->getEditModeContent($this);
		}
		catch (BannerMachineRuntimeException $e) {

			$bannerContent = '<h1>BANNER NOT FOUND</h1>';
		}

		$response->assign('bannerContent', $bannerContent);
		$response->outputTemplate('banner-bo.html.twig');
	}

	public function getPropertyDefinition()
	{
		$contents = array();

		$html = new \Supra\Editable\String('Append to target URL');
		$html->setDefaultValue('');
		$contents[self::PROPERTY_NAME_APPEND_TO_URL] = $html;

		$bannerProvider = ObjectRepository::getBannerProvider($this);

		$types = $bannerProvider->getTypes();

		$bannerTypesForSelect = array();
		foreach ($types as $type) {
			/* @var $type BannerTypeAbstraction */

			$bannerTypesForSelect[$type->getId()] = $type->getName();
		}

		$html = new \Supra\Editable\Select('Banner type');
		$html->setValues($bannerTypesForSelect);
		$html->setDefaultValue($type->getId());
		
		$contents[self::PROPERTY_NAME_BANNER_TYPE] = $html;

		return $contents;
	}

}
