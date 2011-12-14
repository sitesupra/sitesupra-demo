<?php

namespace Supra\Cms\BannerManager\Banneredit;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\Entity\ImageBanner;
use Supra\FileStorage\Entity\Image;
use Supra\FileStorage\Entity\File;
use \DateTime;
use Supra\BannerMachine\BannerProvider;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\BannerMachine\Entity\Banner;
use Doctrine\ORM\EntityRepository;
use Supra\Controller\Pages\Entitsy\Page;

class BannereditAction extends CmsAction
{

	/**
	 * @var BannerProvider
	 */
	protected $bannerProvider;

	/**
	 * @var EntityRepository
	 */
	protected $pageLocalizationRepository;

	/**
	 * @var EntityRepository
	 */
	protected $imageFileRepository;


	function __construct()
	{
		parent::__construct();

		$this->bannerProvider = ObjectRepository::getBannerProvider($this);

		$em = $this->bannerProvider->getEntityManager();

		$this->pageRepository = $em->getRepository(PageLocalization::CN());
		$this->imageFileRepository = $em->getRepository(Image::CN());
	}

	/**
	 * Stores updated banner data.
	 */
	public function saveAction()
	{
		$postData = $this->getRequest()
				->getPost();

		\Log::debug('BANNER SAVE REQUEST: ', $postData->getArrayCopy());

		$banner = $this->bannerProvider->getBanner($postData->get('banner_id'));

		$this->updateBannerFromPost($banner, $postData);

		$this->getResponse()->setResponseData(null);
	}

	/**
	 * Stores new banner.
	 */
	public function insertAction()
	{
		$postData = $this->getRequest()
				->getPost();

		\Log::debug('BANNER INSERT REQUEST: ', $postData->getArrayCopy());

		$banner = new ImageBanner();

		$this->updateBannerFromPost($banner, $postData);

		$this->getResponse()->setResponseData(null);
	}

	/**
	 * Takes banner object and post data and updates objects properties and stores it.
	 * @param Banner $banner
	 * @param type $postData 
	 */
	private function updateBannerFromPost(Banner $banner, $postData)
	{
		$banner->setStatus($postData->get('status', 1));
		$banner->setPriority($postData->get('priority', 5));

		$schedule = $postData->getChild('schedule');
		$banner->setScheduledFrom(new DateTime($schedule->get('from', 'now')));
		$banner->setScheduledTill(new DateTime($schedule->get('to', 'tomorrow')));

		$bannerTarget = $postData->getChild('target');

		if ($bannerTarget->get('resource') == 'link') {
			$banner->setExternalTarget($bannerTarget->get('href'));
		}
		else {
			$banner->setInternalTarget($bannerTarget->get('page_id'));
		}

		$banner->setTypeId($postData->get('group_id', 'unknown-banner-type'));

		$imageFile = $this->imageFileRepository->find($postData->get('image'));

		$banner->setFile($imageFile);

		$banner->setLocaleId($postData->get('locale'));

		$bannerType = $this->bannerProvider->getType($banner->getTypeId());

		$bannerType->validate($banner);

		$this->bannerProvider->store($banner);
	}

	/**
	 * Reads banner from database and returns JSON for client side.
	 */
	public function loadAction()
	{
		$requestData = $this->getRequest()->getQuery();

		\Log::debug('POST DATA: ', $requestData);

		$bannerId = $requestData->get('banner_id', null);

		if (empty($bannerId)) {
			throw new Exception\RuntimeException('Banner id not posted.');
		}

		$fileStorage = ObjectRepository::getFileStorage($this);

		$banner = $this->bannerProvider->getBanner($bannerId);

		$result = array(
				'banner_id' => $banner->getId(),
				'group_id' => $banner->getTypeId(),
				'priority' => $banner->getPriority(),
				'schedule' => array(
						'from' => $banner->getScheduledFrom()->format('Y-m-d'),
						'to' => $banner->getScheduledTill()->format('Y-m-d')
				),
				'status' => $banner->getStatus(),
				'stats' => array(
						'exposures' => $banner->getExposureCount(),
						'ctr' => $banner->getCtr(),
						'average_ctr' => $banner->getAverageCtr()
				)
		);

		if ($banner->getTargetType() == Banner::TARGET_TYPE_INTERNAL) {

			/* @var $page Page */
			$page = $this->pageRepository->find($banner->getInternalTarget());

			if (empty($page)) {

				$result['target'] = array();
			}
			else {

				$result['target'] = array(
						'resource' => 'page',
						'page_id' => $banner->getInternalTarget(),
						'href' => '#',
						'title' => $page->getTitle()
				);
			}
		}
		else {

			$result['target'] = array(
					'resource' => 'link',
					'page_id' => null,
					'href' => $banner->getExternalTarget(),
					'title' => $banner->getExternalTarget()
			);
		}

		if ($banner instanceof ImageBanner) {

			$type = $this->bannerProvider->getType($banner->getTypeId());

			$path = array(0); // !!! Important !!!
			foreach ($banner->getFile()->getAncestors() as $ancestor) {
				$path[] = $ancestor->getId();
			}

			$result['image'] = array(
					'id' => $banner->getFile()->getId(),
					'path' => $path,
					'external_path' => $fileStorage->getWebPath($banner->getFile()),
					'width' => $type->getWidth(),
					'height' => $type->getHeight()
			);
		}

		$this->getResponse()->setResponseData($result);
	}

	public function deleteAction()
	{
		$request = $this->getRequest();

		$bannerId = $request->getParameter('banner_id');

		$banner = $this->bannerProvider->getBanner($bannerId);

		$this->bannerProvider->remove($banner);

		$this->getResponse()
				->setResponseData(null);
	}

}
