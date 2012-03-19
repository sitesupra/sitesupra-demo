<?php

namespace Supra\Mailer\MassMail\Manager;

use Supra\Mailer\MassMail\Entity;
use Doctrine\ORM\EntityRepository;
use Supra\Mailer\Exception;

class SubscriberManager extends MassMailManager
{

	/**
	 * Repository for subscriber entities
	 * @var  ORM\EntityRepository
	 */
	protected $subscriberRepository;

	public function __construct($entityManager)
	{
		parent::__construct($entityManager);

		$this->subscriberRepository =
				$this->entityManager->getRepository('Supra\Mailer\MassMail\Entity\Subscriber');
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

		$activeSubscriber = $this->getSubscriberByEmail($email, null, true);

		if ( ! empty($activeSubscriber)) {
			throw new Exception\RuntimeException("Subscriber with {$email} already exists and activated");
		}

		$subscriber = new Entity\Subscriber();
		$this->entityManager->persist($subscriber);
		$subscriber->setName($name);
		$subscriber->setEmailAddress($email);
		$subscriber->setActive($active);
		$subscriber->generateConfirmHash();

		return $subscriber;
	}

	/**
	 * Delete subscribers by parameters
	 * @param string $email
	 * @param string|null $hash 
	 * @param bool|null $active
	 */
	public function removeSubscribersByEmail($email, $hash = null, $active = null)
	{
		$subscribers = $this->getSubscriberByEmail($email, $hash, $active);
		foreach ($subscribers as $entity) {
			$this->entityManager->remove($entity);
		}
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

	/**
	 * Activate subscriber
	 * @param Entity\Subscriber $subscriberToActivate 
	 */
	public function activateSubscriber(Entity\Subscriber $subscriberToActivate)
	{
		$subscriberToActivate->setActive(true);
		$email = $subscriberToActivate->getEmailAddress();
		$subscribersSet = $this->getSubscriberByEmail($email, null, false);

		foreach ($subscribersSet as $subscriberEntity) {

			/* @var $subscriberEntity Entity\Subscriber */
			if ($subscriberEntity->getId() != $subscriberToActivate->getId()) {
				$this->entityManager->remove($subscriberEntity);
			}
		}
	}

	/**
	 * Return subscribers by parameters
	 * @param string $email
	 * @param string|null $hash
	 * @param bool|null $active
	 * @return Supra\Mailer\MassMail\Entity\Subscriber[]
	 */
	public function getSubscriberByEmail($email, $hash = null, $active = null)
	{

		$params = array('emailAddress' => $email);

		if ( ! empty($hash)) {
			$params['confirmHash'] = $hash;
		}

		if ($active !== null) {
			$params['active'] = (bool) $active;
		}

		$result = $this->subscriberRepository->findBy($params);

		return $result;
	}

	/**
	 * Unsubscribe user (remove subscriber entity)
	 * @param string $email
	 * @param string $hash
	 * @return \Supra\Mailer\MassMail\Entity\Subscriber 
	 */
	public function unsubscribeByEmail($email, $hash)
	{
		$subscriber = $this->getSingleSubscriberByEmail($email, $hash);

		if ( ! empty($subscriber)) {
			$this->dropSubscriber($subscriber);
			return $subscriber;
		}

		return null;
	}

	/**
	 * Return first in set subscriber by email
	 * @param string $email
	 * @param string $hash
	 * @param bool $active
	 * @return Entity\Subscriber|null
	 */
	public function getSingleSubscriberByEmail($email, $hash = null, $active = null)
	{
		$subscriber = $this->getSubscriberByEmail($email, $hash, $active);

		if (empty($subscriber[0])) {
			return null;
		}

		return $subscriber[0];
	}

}
