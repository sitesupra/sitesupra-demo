<?php

namespace Supra\Package\Framework\Entity;

/**
 * Session data persistency layer. Data is base64_encoded since e.g. Postgres hates null's
 *
 * @Entity
 */
class SessionData
{
	/**
	 * @Id
	 * @GeneratedValue(strategy="AUTO")
	 * @Column(type="integer")
	 * @var int
	 */
	protected $id;

	/**
	 * @Column(type="string", length=40, name="session_id")
	 * @var string
	 */
	protected $sessionId;

	/**
	 * @Column(type="text", name="session_data")
	 * @var string
	 */
	protected $data;

	/**
	 * @Column(type="integer", name="session_timestamp")
	 * @var integer
	 */
	protected $timestamp;

	public function __construct()
	{
		$this->timestamp = time();
	}

	/**
	 * @return int
	 */
	public function getTimestamp()
	{
		return $this->timestamp;
	}

	/**
	 * @param int $timestamp
	 */
	public function setTimestamp($timestamp)
	{
		$this->timestamp = $timestamp;
	}

	/**
	 * @return string
	 */
	public function getData()
	{
		return base64_decode($this->data);
	}

	/**
	 * @param string $data
	 */
	public function setData($data)
	{
		$this->data = base64_encode($data);
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getSessionId()
	{
		return $this->sessionId;
	}

	/**
	 * @param string $sessionId
	 */
	public function setSessionId($sessionId)
	{
		$this->sessionId = $sessionId;
	}

}
