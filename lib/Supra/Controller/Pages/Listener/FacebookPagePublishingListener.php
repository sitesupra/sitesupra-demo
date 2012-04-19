<?php

namespace Supra\Controller\Pages\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Supra\Controller\Pages\Event\CmsPagePublishEventArgs;
use Supra\Controller\Pages\Event\CmsPageDeleteEventArgs;
use Supra\Controller\Pages\PageController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Entity\PageBlock;
use Supra\Social\Facebook;
use Supra\Social\Facebook\Entity\UserFacebookPage;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Event\CmsPageEventArgs;
use Supra\User\Entity\User;
use Supra\Social\Facebook\Entity\UserFacebookData;

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
		return array(CmsPageEventArgs::postPageDelete, CmsPageEventArgs::postPagePublish);
	}

//	public function postPageMove(LifecycleEventArgs $eventArgs)
//	{
//		// will fail
//		$localization = $eventArgs->localization;
//		/* @var $localization Supra\Controller\Pages\Entity\ApplicationLocalization */
//		if ( ! $localization->isPublic()) {
//			$this->togglePageOnFacebook($eventArgs, false);
//		}
//	}

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
	 * @param CmsPageEventArgs $eventArgs
	 * @param boolean $publish
	 */
	private function togglePageOnFacebook(CmsPageEventArgs $eventArgs, $publish)
	{
		$user = $eventArgs->user;
		
		$localization = $eventArgs->localization;
		if ( ! $localization instanceof PageLocalization) {
			$this->logger->info('Expected PageLocalization');
			return;
		}

		/* @var $localization Supra\Controller\Pages\Entity\ApplicationLocalization */

		$facebookBlock = $this->getFacebookBlock($eventArgs);
		/* @var $facebookBlock PageBlock */
		if (is_null($facebookBlock)) {
			if ($publish) {
				// check was block deleted or not? 
				$em = ObjectRepository::getEntityManager($this);
				$repo = $em->getRepository(UserFacebookPage::CN());
				$page = $repo->findOneByPageLocalization($localization->getId());

				if ($page instanceof UserFacebookPage) {
					try {
						$facebook = new Facebook\Adapter($page->getUserData()->getUser());
						$facebook->removeTabFromPage($page);
					} catch (Facebook\Exception\FacebookApiException $e) {
						// if we receive "has not authorized application" exception - then removing already stored data
						$this->logger->info($e->getMessage());

						if ((strpos($e->getMessage(), 'has not authorized application') != false)
								|| $e->getCode() == Facebook\Exception\FacebookApiException::CODE_PERMISSIONS_PROBLEM) {
							if ($user instanceof User) {
								$this->deactivateUserDataRecord($user);
							}
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
		$repo = $em->getRepository(UserFacebookPage::CN());
		$page = $repo->findOneByPageId($pageId);

		if ( ! $page instanceof UserFacebookPage) {
			$this->logger->info('Could not find page with id ' . $pageId);
			return;
		}
		
		$facebook = new Facebook\Adapter($page->getUserData()->getUser());

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
				
				if ($user instanceof User) {
					$this->deactivateUserDataRecord($user);
				}
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

	private function getFacebookBlock(CmsPageEventArgs $eventArgs)
	{
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

		return null;
	}
	
	private function deactivateUserDataRecord(User $user)
	{
		$em = ObjectRepository::getEntityManager($this);
		$userDataRepo = $em->getRepository(UserFacebookData::CN());
		$userDataRecord = $userDataRepo->findOneByUser($user->getId());
		
		if ($userDataRecord instanceof UserFacebookData) {
			$userDataRecord->setActive(false);
			$em->flush($userDataRecord);
		}
	}

}
