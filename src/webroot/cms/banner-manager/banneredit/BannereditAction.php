<?php

namespace Supra\Cms\BannerManager\Banneredit;

use Supra\Cms\CmsAction;
use Supra\ObjectRepository\ObjectRepository;
use Supra\BannerMachine\Entity\ImageBanner;
use Supra\BannerMachine\Entity\FlashBanner;
use Supra\FileStorage\Entity\Image;
use Supra\FileStorage\Entity\File;
use \DateTime;
use Supra\BannerMachine\BannerProvider;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\BannerMachine\Entity\Banner;
use Doctrine\ORM\EntityRepository;
use Supra\Controller\Pages\Entitsy\Page;
use Supra\Cms\Exception\CmsException;
use Supra\Request\RequestData;

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

	/**
	 * @var EntityRepository
	 */
	protected $fileRepository;

	function __construct()
	{
		parent::__construct();

		$this->bannerProvider = ObjectRepository::getBannerProvider($this);

		$em = $this->bannerProvider->getEntityManager();

		$this->pageRepository = $em->getRepository(PageLocalization::CN());
		$this->imageFileRepository = $em->getRepository(Image::CN());
		$this->fileRepository = $em->getRepository(File::CN());
	}

	/**
	 * Stores updated banner data.
	 */
	public function saveAction()
	{
		$postData = $this->getRequest()
				->getPost();

		//\Log::debug('BANNER SAVE REQUEST: ', $postData->getArrayCopy());

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

		//\Log::debug('BANNER INSERT REQUEST: ', $postData->getArrayCopy());

		$file = $this->fileRepository->find($postData->get('image'));

		if (empty($file)) {
			throw new CmsException(null, 'Banner file not chosen.');
		}

		$banner = null;

		if ($file instanceof Image) {
			$banner = new ImageBanner();
		} else {

			$mimeType = $file->getMimeType();

			if ($mimeType == FlashBanner::MIME_TYPE) {
				$banner = new FlashBanner();
			} else {
				throw new \Supra\Cms\Exception\CmsException('Files with type "' . $mimeType . '" are not supported.');
			}
		}

		$this->updateBannerFromPost($banner, $postData);

		$this->getResponse()->setResponseData(null);
	}

	/**
	 * Takes banner object and post data and updates objects properties and stores it.
	 * @param Banner $banner
	 * @param RequestData $postData 
	 */
	private function updateBannerFromPost(Banner $banner, $postData, File $file = null)
	{
		$banner->setStatus($postData->get('status', 1));
		$banner->setPriority($postData->get('priority', 5));

		$schedule = $postData->getChild('schedule');

		if ($schedule->get('from', false)) {
			$banner->setScheduledFrom(new DateTime($schedule->get('from')));
		}

		if ($schedule->get('to', false)) {
			$banner->setScheduledTill(new DateTime($schedule->get('to')));
		}

		if ( ! $postData->hasChild('target')) {

			$banner->setExternalTarget('#');
			
		} else {
			
			$bannerTarget = $postData->getChild('target');

			if ($bannerTarget->get('resource') == 'link') {
				$banner->setExternalTarget($bannerTarget->get('href'));
			} else {
				$banner->setInternalTarget($bannerTarget->get('page_id'));
			}
		}

		$banner->setTypeId($postData->get('group_id', 'unknown-banner-type'));

		$bannerFile = $this->fileRepository->find($postData->get('image'));

		$banner->setFile($bannerFile);

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

		$banner = $this->bannerProvider->getBanner($bannerId);

		$schedule = array('from' => null, 'to' => null);
		$from = $banner->getScheduledFrom();
		$to = $banner->getScheduledTill();

		if ( ! empty($from)) {
			$schedule['from'] = $to->format('Y-m-d');
		}
		if ( ! empty($to)) {
			$schedule['to'] = $to->format('Y-m-d');
		}

		$result = array(
			'banner_id' => $banner->getId(),
			'group_id' => $banner->getTypeId(),
			'priority' => $banner->getPriority(),
			'schedule' => $schedule,
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
			} else {

				$result['target'] = array(
					'resource' => 'page',
					'page_id' => $banner->getInternalTarget(),
					'href' => '#',
					'title' => $page->getTitle()
				);
			}
		} else {

			$result['target'] = array(
				'resource' => 'link',
				'page_id' => null,
				'href' => $banner->getExternalTarget(),
				'title' => $banner->getExternalTarget()
			);
		}

		$type = $this->bannerProvider->getType($banner->getTypeId());

		$path = array(0); // !!! Important !!!
		foreach ($banner->getFile()->getAncestors() as $ancestor) {
			$path[] = $ancestor->getId();
		}

		$result['image'] = array(
			'id' => $banner->getFile()->getId(),
			'path' => $path,
			'external_path' => $banner->getExternalPath(),
			'width' => $type->getWidth(),
			'height' => $type->getHeight()
		);

		$this->getResponse()->setResponseData($result);
	}

	public function deleteAction()
	{
		$request = $this->getRequest();

		$bannerId = $request->getPostValue('banner_id');

		$banner = $this->bannerProvider->getBanner($bannerId);

		$this->bannerProvider->remove($banner);

		$this->getResponse()
				->setResponseData(null);
	}

}
