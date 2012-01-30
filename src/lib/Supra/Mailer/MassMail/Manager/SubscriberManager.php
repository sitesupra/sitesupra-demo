<?php

namespace Supra\Mailer\MassMail\Manager;

use Supra\Mailer\MassMail\Entity;

class SubscriberManager extends MassMailManager
{
	public function __construct($entityManager)
	{
		parent::__construct($entityManager);
	}

	/**
	 * Create new subscriber
	 * @param string $email
	 * @param string $name
	 * @param bool $active
	 * @return Entity\Subscriber 
	 */
	public function createSubscriber($email, $name, $active = false)
	{
		$subscriber = new Entity\Subscriber();
		$this->entityManager->persist($subscriber);
		$subscriber->setName($name);
		$subscriber->setEmailAddress($email);
		$subscriber->setActive($active);

		return $subscriber;
	}

	/**
	 * Remove subscriber
	 * @param Entity\Subscriber $subscriber 
	 */
	public function dropSubscriber(Entity\Subscriber $subscriber)
	{
		$this->entityManager->remove($subscriber);
	}

	/**
	 * Add subscriber to list
	 * @param Entity\Subscriber $subscriber
	 * @param Entity\SubscriberList $list 
	 */
	public function addToList(Entity\Subscriber $subscriber, Entity\SubscriberList $list)
	{
		$list->addSubscriber($subscriber);
	}

	/**
	 * Remove subscriber from list
	 * @param Entity\Subscriber $subscriber
	 * @param Entity\SubscriberList $list 
	 */
	public function removeFromList(Entity\Subscriber $subscriber, Entity\SubscriberList $list)
	{
		$list->removeSubscriber($subscriber);
	}

}
