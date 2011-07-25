<?php

namespace Supra\FileStorage\Entity;

/**
 * File localised metadata object
 * @Entity
 * @Table(name="file_localisation")
 */
class MetaData extends Abstraction\Entity
{

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $locale;

	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;

	/**
	 * @Column(type="string",nullable=true)
	 * @var string
	 */
	protected $description;

	/**
	 * @ManyToOne(targetEntity="File", cascade={"persist", "remove"})
	 * @JoinColumn(name="master_id", referencedColumnName="id", nullable=true)
	 * @var Page
	 */
	protected $master;

	/**
	 * Construct
	 * @param string $locale
	 */
	public function __construct($locale)
	{
		$this->setLocale($locale);
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param string $locale
	 */
	protected function setLocale($locale)
	{
		$this->locale = $locale;
	}

	/**
	 * @return string
	 */
	public function getLocale()
	{
		return $this->locale;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}

	/**
	 * @return string
	 */
	public function getTitle()
	{
		return $this->title;
	}

	/**
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}

	/**
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Set master object (file)
	 * @param File $master
	 */
	public function setMaster(File $master)
	{
		// TODO match/writeOnce
		$this->master = $master;
		$master->setMetaData($this);
	}

	/**
	 * Get master object (file)
	 * @return File
	 */
	public function getMaster()
	{
		return $this->master;
	}

}
