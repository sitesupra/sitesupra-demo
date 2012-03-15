<?php

namespace Supra\Mailer\SendGrid;

use Supra\Mailer\SendGrid\SmtpApiHeader;
use Supra\Mailer\MassMail\Entity;
use Supra\ObjectRepository\ObjectRepository;

class SendQueueManager extends \Supra\Mailer\MassMail\Manager\SendQueueManager
{

	/**
	 * Send items from queueu
	 * @param int $limit 
	 */
	public function send($limit = 100)
	{

		$campaignGroup = array();

		$criteria = array('status' => Entity\SendQueueItem::STATUS_PREPARED);

		$repository = $this->entityManager->getRepository(Entity\SendQueueItem::CN());

		/**
		 * @todo Add some data lock or some protection to process same data by another process
		 */
		
		$result = $repository->findBy($criteria, array('id' => 'ASC'), $limit);
		
		foreach ($result as $index => $queueItem) {

			$campaign = $queueItem->getCampaign();
			$campaignId = $campaign->getId();
			$campaignGroup[$campaignId]['campaignId'] = $campaignId;
			$campaignGroup[$campaignId]['body'] = $campaign->getHtmlContent();
			$campaignGroup[$campaignId]['subject'] = $campaign->getSubject();
			$campaignGroup[$campaignId]['email'][$index] = $queueItem->getEmailTo();
			$campaignGroup[$campaignId]['from'] = array('email' => $queueItem->getEmailFrom(),
				'name' => $queueItem->getNameFrom());
			$campaignGroup[$campaignId]['queueItemId'][$index] = $queueItem->getId();

			$templateVars = $queueItem->getTemplateVar();

			foreach ($templateVars as $k => $v) {
				$campaignGroup[$campaignId]['replacements'][$k][$index] = $v;
			}
		}

		foreach ($campaignGroup as $messagesSet) {
			$this->sendMessagesSet($messagesSet);
		}
	}

	protected function sendMessagesSet($messageData)
	{


		try {

			$sendGridHeader = new SmtpApiHeader();
			$sendGridHeader->addTo($messageData['email']);
			$sendGridHeader->setUniqueArgs(array('campaignId' => $messageData['campaignId']));

			$mailer = ObjectRepository::getMailer($this);
			$message = new \Supra\Mailer\Message\SimpleMessage();

			$headers = $message->getHeaders();
			$headers->addTextHeader('X-SMTPAPI', $sendGridHeader->asJSON());
			$message->setSubject($messageData['subject']);
			$message->setFrom($messageData['from']['email'], $messageData['from']['name']);
			$message->setTo('example@example.com');
			$message->addPart($messageData['body'], 'text/html');
			$mailer->send($message);

			$this->updateQueueItemSetStatus($messageData['queueItemId'], Entity\SendQueueItem::STATUS_SENT);
		} catch (\Exception $e) {
			$this->updateQueueItemSetStatus($messageData['queueItemId'], Entity\SendQueueItem::STATUS_ERROR_ON_SEND);
			$this->log->error("Can't send emails set from Mass Mail using SendGrid; ", (string) $e);
		}
	}

}

