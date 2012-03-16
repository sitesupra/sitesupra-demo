<?php

namespace Supra\Mailer\MassMail;

use Supra\Mailer\MassMail\Entity;

class MassMailContent
{
	const TYPE_HTML_CONTENT = 10;
	const TYPE_TEXT_CONTENT = 20;
	const TYPE_SUBJECT = 30;

	/**
	 * Raw content
	 * @var string
	 */
	protected $content;

	/**
	 * Content type
	 * @var int
	 */
	protected $type;

	public function __construct($type, $content)
	{
		$this->type = (int) $type;
		$this->content = $content;
	}

	/**
	 * Returns common template tags replacement for subscriber
	 * @param Entity\Subscriber $subscriber
	 * @return array
	 */
	public function getCommonReplacementsValues(Entity\Subscriber $subscriber)
	{
		$replacements = array(
			'%subscriberName%' => $subscriber->getName(),
			'%subscriberEmail%' => $subscriber->getEmailAddress());

		return $replacements;
	}
	
	/**
	 * Prepare (make replacements) content
	 * @param Entity\Subscriber $subscriber
	 * @return string
	 */
	public function getPreparedContent(Entity\Subscriber $subscriber)
	{
		//common replacement
		$preparedContent = strtr($this->content, $this->getCommonReplacementsValues($subscriber));

		/**
		 * @todo implement some content type-depended replacements
		 */
		switch ($this->type) {

			case self::TYPE_HTML_CONTENT: {
					
				}break;

			case self::TYPE_TEXT_CONTENT: {
					
				}break;

			case self::TYPE_SUBJECT: {
					
				}break;
		}

		return $preparedContent;
	}

}

