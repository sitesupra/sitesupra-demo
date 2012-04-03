<?php

namespace Supra\Configuration\Writer;

use Doctrine\ORM\EntityManager;
use Supra\Configuration\Entity\IniConfigurationItem;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Configuration\Parser\DatabaseParser;

class DatabaseWriter extends AbstractWriter
{

	/**
	 * @var EntityManager
	 */
	protected $entityManger;

	/**
	 * @var array
	 */
	protected $loadedIniItems;

	public function getLoadedIniItems()
	{
		return $this->loadedIniItems;
	}

	public function setLoadedIniItems($loadedIniItems)
	{
		$this->loadedIniItems = $loadedIniItems;
	}

	/**
	 * @return EntityManager
	 */
	public function getEntityManger()
	{
		if (empty($this->entityManager)) {
			$this->entityManager = ObjectRepository::getEntityManager($this);
		}

		return $this->entityManger;
	}

	/**
	 * @param EntityManager $entityManger 
	 */
	public function setEntityManger(EntityManager $entityManger)
	{
		$this->entityManger = $entityManger;
	}

	public function write()
	{
		$em = $this->getEntityManager();

		$data = $this->data;

		foreach ($data as $sectionName => $sectionData) {

			foreach ($sectionData as $name => $value) {

				$iniItem = $this->getIniItem($sectionName, $name);
				$iniItem->setValue($value);

				$em->persist($iniItem);
			}
		}

		$em->flush();
	}

	/**
	 * @param string $sectionName
	 * @param string $name
	 * @return IniConfigurationItem 
	 */
	protected function getIniItem($sectionName, $name)
	{
		$uniqueName = IniConfigurationItem::makeUniqueName($this->filename, $sectionName, $name);

		$loadedIniItems = $this->getLoadedIniItems();

		$iniItem = null;

		if ( ! empty($loadedIniItems[$uniqueName])) {
			$iniItem = $loadedIniItems[$uniqueName];
		} else {
			$iniItem = new IniConfigurationItem();
			$iniItem->setSection($sectionName);
			$iniItem->setName($name);
			$iniItem->setFilename($this->filename);
		}

		return $iniItem;
	}

	public function setParser(AbstractParser $parser)
	{
		if ($parser instanceof DatabaseParser) {
			
			$this->setEntityManger($parser->getEntityManager());
			$this->setLoadedIniItems($parser->getLoadedIniItems());
		}
	}

}
