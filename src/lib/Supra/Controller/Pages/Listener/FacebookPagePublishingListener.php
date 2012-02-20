<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\Common\EventSubscriber;
use Supra\Cms\CmsController;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Event\CmsPagePublishEventArgs;
use Supra\Controller\Pages\Event\CmsPageDeleteEventArgs;
use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageBlock;
use Supra\Social\Facebook;
use Project\SocialMedia\SocialMediaController;
use Supra\User\Entity\UserFacebookPage;
use Supra\Controller\Pages\Entity\PageLocalization;

class FacebookPagePublishingListener implements EventSubscriber
{

	/**
	 * @var \Supra\Log\Writer\WriterAbstraction
	 */
	private $logger = null;

	public function __construct()
	{
		$this->logger = ObjectRepository::getLogger($this);
	}

	/**
	 * {@inheritdoc}
	 * @return array
	 */
	public function getSubscribedEvents()
	{
//		return array(PagePathGenerator::postPageMove, CmsController::EVENT_POST_PAGE_PUBLISH, CmsController::EVENT_POST_PAGE_DELETE);
		return array(CmsController::EVENT_POST_PAGE_PUBLISH, CmsController::EVENT_POST_PAGE_DELETE);
	}

	public function postPageMove(LifecycleEventArgs $eventArgs)
	{
		// will fail
//		$localization = $eventArgs->localization;
//		/* @var $localization Supra\Controller\Pages\Entity\ApplicationLocalization */
//		if ( ! $localization->isPublic()) {
//			$this->togglePageOnFacebook($eventArgs, false);
//		}
	}

	/**
	 * @param CmsPagePublishEventArgs $eventArgs 
	 */
	public function postPagePublish(CmsPagePublishEventArgs $eventArgs)
	{
		$this->togglePageOnFacebook($eventArgs, true);
	}

	public function postPageDelete(CmsPageDeleteEventArgs $eventArgs)
	{
		$this->togglePageOnFacebook($eventArgs, false);
	}

	/**
	 * @param CmsPageDeleteEventArgs $eventArgs
	 * @param boolean $publish
	 */
	private function togglePageOnFacebook($eventArgs, $publish)
	{
		$localization = $eventArgs->localization;
		if ( ! $localization instanceof PageLocalization) {
			$this->logger->info('Expected PageLocalization');
			return;
		}

		$user = $eventArgs->user;
		/* @var $localization Supra\Controller\Pages\Entity\ApplicationLocalization */
		/* @var $user Supra\User\Entity\User */
		$facebook = new Facebook\Adapter($user);

		$facebookBlock = $this->getFacebookBlock($eventArgs);
		/* @var $facebookBlock PageBlock */
		if (is_null($facebookBlock)) {
			if ($publish) {
				// check was block deleted or not? 
				$em = ObjectRepository::getEntityManager($this);
				$repo = $em->getRepository('Supra\User\Entity\UserFacebookPage');
				$page = $repo->findOneByPageLocalization($localization->getId());

				if ($page instanceof UserFacebookPage) {
					try {
						$facebook->removeTabFromPage($page);
					} catch (Facebook\Exception\FacebookApiException $e) {
						// if we receive "has not authorized application" exception - then removing already stored data
						$this->logger->info($e->getMessage());

						if ((strpos($e->getMessage(), 'has not authorized application') != false)
								|| $e->getCode() == Facebook\Exception\FacebookApiException::CODE_PERMISSIONS_PROBLEM) {
							SocialMediaController::deactivateUserDataRecord($user);
						}

						return;
					}
					
					$this->logger->debug('Unpublishing page on the facebook');
				}
			}

			$this->logger->debug('Failed to find facebook block on a page');
			return;
		}

		$properties = $this->getBlockProperties($facebookBlock);

		if (empty($properties['available_pages'])) {
			$this->logger->info('Facebook page id is empty');
			return;
		}

		$pageId = $properties['available_pages'];



		$em = ObjectRepository::getEntityManager($this);
		$repo = $em->getRepository('Supra\User\Entity\UserFacebookPage');
		$page = $repo->findOneByPageId($pageId);

		if ( ! $page instanceof UserFacebookPage) {
			$this->logger->info('Could not find page with id ' . $tabId);
			return;
		}

		try {
			if ($publish) {
				$facebook->addTabToPage($page, $properties['tab_name']);
			} else {
				$facebook->removeTabFromPage($page);
			}
		} catch (Facebook\Exception\FacebookApiException $e) {
			// if we receive "has not authorized application" exception - then removing already stored data
			$this->logger->info($e->getMessage());

			if ((strpos($e->getMessage(), 'has not authorized application') != false)
					|| $e->getCode() == Facebook\Exception\FacebookApiException::CODE_PERMISSIONS_PROBLEM) {
				SocialMediaController::deactivateUserDataRecord($user);
			}

			return;
		}

		$localization = $em->find(PageLocalization::CN(), $localization->getId());

		$em->persist($page);

		if ( ! $publish)
			$localization = null;

		$page->setPageLocalization($localization);
		$em->flush();
	}

	/**
	 * @param PageBlock $block 
	 * @return array
	 */
	private function getBlockProperties(PageBlock $block)
	{
		$values = $block->getBlockProperties()->getValues();
		$properties = array();

		foreach ($values as $value) {
			/* @var $value Supra\Controller\Pages\Entity\BlockProperty */
			$properties[$value->getName()] = $value->getValue();
		}

		return $properties;
	}

	private function getFacebookBlock($eventArgs)
	{
		$facebookBlock = null;

		// fetch FB page block ids and then check if block was removed
		$values = $eventArgs->localization->getPlaceHolders()->getValues();

		foreach ($values as $value) {
			/* @var $value \Supra\Controller\Pages\Entity\Abstraction\PlaceHolder */
			$blocks = $value->getBlocks()->getValues();
			foreach ($blocks as $block) {
				/* @var $block \Supra\Controller\Pages\Entity\PageBlock */
				if ($block->getComponentClass() == 'Supra\Social\Facebook\FacebookBlock') {
					return $block;
				}
			}
		}

		return $fbBlock;
	}

}