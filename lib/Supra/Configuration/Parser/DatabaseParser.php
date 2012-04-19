<?php

namespace Supra\Configuration\Parser;

use Supra\Configuration\Entity\IniConfigurationItem;
use Doctrine\ORM\EntityManager;
use Supra\ObjectRepository\ObjectRepository;

class DatabaseParser extends AbstractParser
{

	/**
	 * @var EntityManager
	 */
	protected $entityManager;

	/**
	 * @var array
	 */
	protected $loadedIniItems;

	function __construct()
	{
		parent::__construct();

		$this->loadedIniItems = array();
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManager()
	{
		if (empty($this->entityManager)) {
			$this->entityManager = ObjectRepository::getEntityManager($this);
		}

		return $this->entityManager;
	}

	/**
	 * @param EntityManager $entityManager 
	 */
	public function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	/**
	 * @param string $filename
	 * @return array
	 */
	public function parseFile($filename)
	{
		$this->filename = $filename;

		$contents = $this->parse($filename);

		$this->filename = null;

		return $contents;
	}

	/**
	 *
	 * @param string $filename
	 * @return array
	 */
	protected function parse($filename)
	{
		$repository = $this->getEntityManager()
				->getRepository(IniConfigurationItem::CN());

		$criteria = array('filename' => $filename);

		$iniItems = $repository->findBy($criteria);

		$data = array();

		foreach ($iniItems as $iniItem) {
			/* @var $iniItem IniConfigurationItem */

			$sectionName = $iniItem->getSection();
			$itemName = $iniItem->getName();
			$data[$sectionName][$itemName] = $iniItem->getValue();

			$this->loadedIniItems[$iniItem->getUniqueName()] = $iniItem;
		}

		return $data;
	}

	/**
	 * @return array
	 */
	public function getLoadedIniItems()
	{
		return $this->loadedIniItems;
	}

}
