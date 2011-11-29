<?php

namespace Supra\Controller\Pages\Task;

use Supra\Controller\Layout\Processor\ProcessorInterface;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Cms\Exception\CmsException;

/**
 * Inserts or updates the layout using layout processor
 */
class LayoutProcessorTask
{
	const STAT_LAYOUT = 'layouts';
	const STAT_PLACEHOLDERS = 'placeholders';
	const STAT_REMOVABLE_PLACEHOLDERS = 'removable_placeholders';
	
	/**
	 * Layout name, usually filename
	 * @var string
	 */
	protected $layoutId;

	/**
	 * @var ProcessorInterface
	 */
	protected $layoutProcessor;
	
	/**
	 * @var EntityManager
	 */
	protected $entityManager;
	
	/**
	 * Whether to remove place holders from database which are not found in layout
	 * @var boolean
	 */
	protected $removePlaceHolders = false;
	
	/**
	 * Resulting layout object
	 * @var Entity\Layout
	 */
	protected $layout;
	
	/**
	 * @var array
	 */
	protected $statistics;

	/**
	 * Resets the statistics array
	 */
	public function __construct()
	{
		$this->resetStatistics();
	}
	
	/**
	 * @param string $layoutId
	 */
	public function setLayoutId($layoutId)
	{
		$this->layoutId = $layoutId;
	}

	/**
	 * @param ProcessorInterface $layoutProcessor
	 */
	public function setLayoutProcessor(ProcessorInterface $layoutProcessor)
	{
		$this->layoutProcessor = $layoutProcessor;
	}
	
	/**
	 * @param EntityManager $entityManager
	 */
	public function setEntityManager(EntityManager $entityManager)
	{
		$this->entityManager = $entityManager;
	}
	
	/**
	 * @param boolean $remove
	 */
	public function removePlaceHolders($remove = true)
	{
		$this->removePlaceHolders = $remove;
	}

	/**
	 * @return Entity\Layout
	 */
	public function getLayout()
	{
		return $this->layout;
	}
	
	/**
	 * @return array
	 */
	public function getStatistics()
	{
		return $this->statistics;
	}
	
	/**
	 * @return array
	 */
	public function resetStatistics()
	{
		$this->statistics = array(
			self::STAT_LAYOUT => array(),
			self::STAT_PLACEHOLDERS => array(),
			self::STAT_REMOVABLE_PLACEHOLDERS => array(),
		);
	}
	
	/**
	 * @return string
	 */
	public function getStatisticsMessage()
	{
		$newLayoutCount = count($this->statistics[self::STAT_LAYOUT]);
		$newPlaceHoldersCount = count($this->statistics[self::STAT_PLACEHOLDERS]);
		$deletableCount = count($this->statistics[self::STAT_REMOVABLE_PLACEHOLDERS]);
		
		$message = "$newLayoutCount new layouts, $newPlaceHoldersCount new placeholders";
		
		if ($deletableCount > 0) {
			if ($this->removePlaceHolders) {
				$message .= ", $deletableCount placeholders removed";
			} else {
				$message .= ", $deletableCount placeholders could be removed";
			}
		}
		
		return $message;
	}

	/**
	 * Inserts or updates layout information in the storage
	 */
	public function perform()
	{
		$this->layout = null;
		$file = $this->layoutId;

		// Search for this layout
		$layoutRepo = $this->entityManager->getRepository(Entity\Layout::CN());
		$layout = $layoutRepo->findOneByFile($file);

		if (is_null($layout)) {
			$layout = new Entity\Layout();
			$this->entityManager->persist($layout);
			$layout->setFile($file);
			
			$this->statistics[self::STAT_LAYOUT][] = $file;
		}
		
		$placeHolders = $layout->getPlaceHolders();
		$processor = $this->layoutProcessor;
		$places = $processor->getPlaces($file);

		foreach ($places as $name) {
			$placeHolder = null;
			
			if ($placeHolders->containsKey($name)) {
				$placeHolder = $placeHolders->get($name);
			} else {
				$placeHolder = new Entity\LayoutPlaceHolder($name);
				$placeHolder->setLayout($layout);
				$this->entityManager->persist($placeHolder);
				
				$this->statistics[self::STAT_PLACEHOLDERS][] = array($file, $name);
			}
		}
		
		// Remove not found place holders which are stored in database
		$placeHolderNames = $layout->getPlaceHolderNames();
		$missingPlaceHolders = array_diff($placeHolderNames, $places);
		
		if ( ! empty($missingPlaceHolders)) {
			foreach ($missingPlaceHolders as $name) {
				if ($placeHolders->containsKey($name)) {
					
					if ($this->removePlaceHolders) {
						$placeHolder = $placeHolders->get($name);
						$this->entityManager->remove($placeHolder);
					}
					
					$this->statistics[self::STAT_REMOVABLE_PLACEHOLDERS][] = array($file, $name);
				}
			}
		}

		// Keep created layout
		$this->layout = $layout;
	}

}
