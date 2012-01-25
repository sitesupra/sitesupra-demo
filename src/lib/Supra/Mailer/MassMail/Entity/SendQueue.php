<?php

namespace Supra\Mailer\MassMail\Entity;

use Supra\Database\Entity;

/**
 * @Entity
 */
class SendQueue extends Entity
{

	/**
	 * @Column(type="string", name="email_to", nullable=false)
	 * @var string
	 */	
	protected $emailTo;

	/**
	 * @Column(type="string", name="name_to", nullable=false)
	 * @var string
	 */
	protected $nameTo;

	
	/**
	 * @Column(type="string", name="email_from", nullable=false)
	 * @var string
	 */
	protected $emailFrom;
	
	/**
	 * @Column(type="string", name="name_from", nullable=false)
	 * @var string
	 */
	protected $nameFrom;
	
	/**
	 * @Column(type="string", name="reply_to", nullable=false)
	 * @var string
	 */
	protected $replyTo;
	
	/**
	 * @Column(type="integer", name="status", nullable=false)
	 * @var string
	 */
	protected $status = 0;

	/**
	 * @Column(type="integer", name="type", nullable=false)
	 * @var string
	 */
	protected $type = 1;
	
	/**
	 * @Column(type="string", name="html_content", nullable=true)
	 * @var string
	 */
	protected $htmlContent;
	
	/**
	 * @Column(type="string", name="text_content", nullable=true)
	 * @var string
	 */
	protected $textContent;
	
	/**
	 * @Column(type="string", name="subject", nullable=false)
	 * @var string
	 */
	protected $subject;
	
	/**
	 * @Column(type="dateTime", name="create_date_time", nullable=false)
	 * @var string
	 */
	protected $createDateTime;
	
	/**
	 * @Column(type="dateTime", name="send_date_time", nullable=true)
	 * @var string
	 */
	protected $sendDateTime;
	
	/**
	 * Returns email-to address
	 * @return string
	 */
	public function getEmailTo()
	{
		return $this->emailTo;
	}

	/**
	 * Returns name-to
	 * @return string
	 */
	public function getNameTo()
	{
		return $this->nameTo;
	}

	/**
	 * Returns email-from address
	 * @return string
	 */
	public function getEmailFrom()
	{
		return $this->emailFrom;
	}

	/**
	 * Returns name-from 
	 * @return string
	 */
	public function getNameFrom()
	{
		return $this->nameFrom;
	}

	/**
	 * Returns reply-to email address
	 * @return type 
	 */
	public function getReplyTo()
	{
		return $this->replyTo;
	}

	/**
	 * Returns record status
	 * @return int
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * Returns record type
	 * @return int
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Returns HTML content
	 * @return struing
	 */
	public function getHtmlContent()
	{
		return $this->htmlContent;
	}

	/**
	 * Returns text content
	 * @return string
	 */
	public function getTextContent()
	{
		return $this->textContent;
	}

	/**
	 * Returns subject
	 * @return string
	 */
	public function getSubject()
	{
		return $this->subject;
	}

	/**
	 * Returns record create date-time
	 * @return string
	 */
	public function getCreateDateTime()
	{
		return $this->createDateTime;
	}

	/**
	 * Returns record send date-time
	 * @return string
	 */
	public function getSendDateTime()
	{
		return $this->sendDateTime;
	}

	/**
	 * Set email-to address
	 * @param string $emailTo 
	 */
	public function setEmailTo($emailTo)
	{
		$this->emailTo = $emailTo;
	}

	/**
	 * Set name-to value
	 * @param type $nameTo 
	 */
	public function setNameTo($nameTo)
	{
		$this->nameTo = $nameTo;
	}

	/**
	 * Set email from address
	 * @param string $emailFrom 
	 */
	public function setEmailFrom($emailFrom)
	{
		$this->emailFrom = $emailFrom;
	}

	/**
	 * Set name from value
	 * @param string $nameFrom 
	 */
	public function setNameFrom($nameFrom)
	{
		$this->nameFrom = $nameFrom;
	}

	/**
	 * Set reply-to address value
	 * @param string $replyTo 
	 */
	public function setReplyTo($replyTo)
	{
		$this->replyTo = $replyTo;
	}

	/**
	 * Set record status
	 * @param integer $status 
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * Set record type
	 * @param int $type 
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * Set HTML content value
	 * @param string $htmlContent 
	 */
	public function setHtmlContent($htmlContent)
	{
		$this->htmlContent = $htmlContent;
	}

	/**
	 * Set text-content alue
	 * @param string $textContent 
	 */
			
	public function setTextContent($textContent)
	{
		$this->textContent = $textContent;
	}

	/**
	 * Set subject value
	 * @param string $subject 
	 */
	public function setSubject($subject)
	{
		$this->subject = $subject;
	}

	/**
	 * Set record create time as now
	 * @param void
	 */
	public function setCreateDateTime()
	{
		$this->createDateTime = $createDateTime;
	}

	
	/**
	 * Set record send time as now
	 * @param void
	 */
	public function setSendDateTime()
	{
		$this->sendDateTime = $sendDateTime;
	}


	
	
}

