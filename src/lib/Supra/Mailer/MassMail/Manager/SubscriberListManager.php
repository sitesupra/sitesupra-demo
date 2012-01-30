<?php
namespace Supra\Mailer\MassMail\Manager;

use Supra\Mailer\MassMail\Entity;

class SubscriberListManager extends MassMailManager
{
	public function __construct($entityManager)
	{
		parent::__construct($entityManager);
	}

	
	/**
	 * Create subscriber list
	 * @param string $title
	 * @return Entity\SubscriberList 
	 */
	public function createList($title)
	{
		$title = trim($title);	
		$list = new Entity\SubscriberList();
		$this->entityManager->persist($list);
		$list->setTitle($title);
		
		return $list;
	}
	
	/**
	 * Remove list
	 * @param Entity\SubscriberList $list 
	 */
	public function dropList(Entity\SubscriberList $list)
	{
		$this->entityManager->remove($list);
	}
	
	/**
	 * Returns list
	 * @param string $listId
	 * @return Entity\SubscriberList
	 */
	public function getList($listId)
	{
		$repository = $this->entityManager->getRepository('\Supra\Mailer\MassMail\Entity\SubscriberList');
		$result = $repository->find($listId);
		
		return $result;
	}
	
	public function getCampaiginsInList() {
		
	}
	
	/**
	 * Returns set of lists by criteria
	 * @param array $criteria
	 * @param string|null $orderBy
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return array
	 */
	public function getListsSet($criteria = array(), $orderBy = null, $limit = null, $offset = null) 
	{
		
		$repository = $this->entityManager->getRepository('\Supra\Mailer\MassMail\Entity\SubscriberList');
		$result = $repository->findBy($criteria, $orderBy, $limit, $offset);
		
		return $result;
	}
	
	/**
	 * Return active list subscribers
	 * @param Entity\SubscriberList $list
	 * @return Entity\Subscriber[]
	 */
	public function getActiveSubscribers(Entity\SubscriberList $list){
		
		$activeSubacribers = array();
		$subscribers = $list->getSubscribers();
		
		foreach($subscribers as $subscriber)
		{	
			if( $subscriber->getActive() ) {
				$activeSubacribers[] = $subscriber;
			}
		}

		return $activeSubacribers;
	}
	
}
