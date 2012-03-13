<?php

namespace Supra\Mailer\MassMail\Manager;

use Supra\Mailer\MassMail\Entity;
use Supra\ObjectRepository\ObjectRepository;

class SendQueueManager extends MassMailManager
{

	public function __construct($entityManager)
	{
		parent::__construct($entityManager);
	}

	/**
	 * Create send queue item
	 * @return Entity\SendQueueItem 
	 */
	public function createQueueItem()
	{
		$sendQueueItem = new Entity\SendQueueItem();
		$this->entityManager->persist($sendQueueItem);

		return $sendQueueItem;
	}

	/**
	 * Send items from queueu
	 * @param int $limit 
	 */
	public function send($limit = 100)
	{
		$criteria = array('status' => Entity\SendQueueItem::STATUS_PREPARED);

		$repository = $this->entityManager->getRepository(Entity\SendQueueItem::CN());
		$result = $repository->findBy($criteria, array('id' => 'ASC'), $limit);

		foreach ($result as $queueItem) {
			$this->sendEmail($queueItem);
		}
	}

	/**
	 * Send email
	 * @param Entity\SendQueueItem $queueItem 
	 */
	protected function sendEmail(Entity\SendQueueItem $queueItem)
	{
		try {

			$mailer = ObjectRepository::getMailer($this);
			$message = new \Supra\Mailer\Message\SimpleMessage();

			$message->setSubject($queueItem->getSubject());
			$message->setFrom($queueItem->getEmailFrom(), $queueItem->getNameFrom());
			$message->setReplyTo($queueItem->getReplyTo());
			$message->setTo($queueItem->getEmailTo(), $queueItem->getNameTo());

			$htmlBody = $queueItem->getHtmlContent();

			if ( ! empty($htmlBody)) {
				$message->addPart($queueItem->getHtmlContent(), 'text/html');
			}

			$textBody = $queueItem->getTextContent();

			if ( ! empty($textBody)) {
				$message->addPart($textBody, 'plain/text');
			}

			$mailer->send($message);
			$queueItem->setStatus(Entity\SendQueueItem::STATUS_SENT);
		} catch (\Exception $e) {
			$queueItem->setStatus(Entity\SendQueueItem::STATUS_ERROR_ON_SEND);
			$this->log->error("Can't send email from Mass Mail; ", (string) $e);
		}
	}
	
	/**
	 * Update status by Ids set
	 * @param array $itemsIdsSet
	 * @param int $status 
	 */
	public function updateQueueItemSetStatus($itemsIdsSet, $status)
	{
		$status = (int) $status;
		
		$this->entityManager->createQuery('UPDATE SendQueue s SET status = :status WHERE id IN (:ids)')
				->setParameter('status', $status)
				->setParameter('ids', $itemsIdsSet)
				->execute();
		
	}

}

