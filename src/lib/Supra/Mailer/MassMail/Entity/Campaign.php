<?php

namespace Supra\Mailer\MassMail\Entity;

use Supra\Database\Entity;

/**
 * @Entity
 */
class Campaign extends Entity
{

	/**
	 * @ManyToOne(targetEntity="SubscriberList")
	 */
	protected $subscriberList;

	/**
	 * @Column(type="string", name="email_address", nullable=false)
	 * @var string
	 */
	protected $emailAddress;

	/**
	 * @Column(type="string", name="subject", nullable=false)
	 * @var string
	 */
	protected $subject;

	/**
	 * @Column(type="string", name="name", nullable=false)
	 * @var string
	 */
	protected $name;

	/**
	 * @Column(type="string", name="from_name", nullable=false)
	 * @var string
	 */
	protected $fromName;

	/**
	 * @Column(type="string", name="from_email", nullable=false)
	 * @var string
	 */
	protected $fromEmail;

	/**
	 * @Column(type="string", name="reply_to", nullable=false)
	 * @var string
	 */
	protected $replyTo;

	/**
	 * @Column(type="string", name="html_url", nullable=true)
	 * @var string
	 */
	protected $htmlUrl;

	/**
	 * @Column(type="string", name="text_url", nullable=true)
	 * @var string
	 */
	protected $textUrl;

}
