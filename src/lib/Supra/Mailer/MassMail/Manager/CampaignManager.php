<?php

namespace Supra\Mailer\MassMail\Manager;

use Supra\Mailer\MassMail\Entity;

class CampaignManager extends MassMailManager
{
	public function __construct($entityManager)
	{
		parent::__construct($entityManager);
	}

	/**
	 * Find campaign by ID
	 * @param string $campaignId
	 * @return Entity\Campaign
	 */
	public function getCampaign($campaignId)
	{
		$repository = $this->entityManager->getRepository(Entity\Campaign::CN());
		$params = array('id' => $campaignId);
		$campaign = $repository->findOneBy($params);

		return $campaign;
	}

	/**
	 * Create and persists new campaign object
	 * @param string $name
	 * @param Entity\SubscriberList $list
	 * @return Entity\Campaign 
	 */
	public function createCampaign($name, Entity\SubscriberList $list)
	{
		$campaign = new Entity\Campaign();
		$this->entityManager->persist($campaign);
		$campaign->setSubscriberList($list);
		$campaign->setName($name);
		$campaign->setStatus(Entity\Campaign::STATUS_NEW);

		return $campaign;
	}

	/**
	 * Assign campaign to subscribers list
	 * @param Entity\SubscriberList $subscriberList
	 * @param Entity\Campaign $campaign 
	 */
	public function assignCampaignToList(Entity\SubscriberList $subscriberList, Entity\Campaign $campaign)
	{
		$campaign->setSubscriberList($subscriberList);
	}

	/**
	 * Drop campaign
	 * @param Entity\Campaign $campaign 
	 */
	public function dropCampaign(Entity\Campaign $campaign)
	{
		$this->entityManager->remove($campaign);
	}

}
