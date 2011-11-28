<?php

namespace Supra\Controller\Pages\Task;

use Supra\Controller\Layout\Processor\ProcessorInterface;
use Supra\Controller\Pages\Entity;
use Doctrine\ORM\EntityManager;
use Supra\Cms\Exception\CmsException;
use Supra\Controller\Layout\Exception as LayoutException;

/**
 * Inserts or updates the layout using layout processor
 */
class LayoutProcessorTask
{
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
	 * Resulting layout object
	 * @var Entity\Layout
	 */
	protected $layout;

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
	 * @return Entity\Layout
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * Inserts or updates layout information in the storage
	 */
	public function perform()
	{
		$file = $this->layoutId;

		// Search for this layout
		$layoutRepo = $this->entityManager->getRepository(Entity\Layout::CN());
		$layout = $layoutRepo->findOneByFile($file);

		if (is_null($layout)) {
			$layout = new Entity\Layout();
			$this->entityManager->persist($layout);
			$layout->setFile($file);
			$processor = $this->layoutProcessor;
			
			try {
				$places = $processor->getPlaces($file);
				
				foreach ($places as $name) {
					$placeHolder = new Entity\LayoutPlaceHolder($name);
					$placeHolder->setLayout($layout);
				}
			} catch (LayoutException\LayoutNotFoundException $e) {
				throw new CmsException('template.error.layout_not_found', null, $e);
			} catch (LayoutException\RuntimeException $e) {
				throw new CmsException('template.error.layout_error', null, $e);
			}
			// Let it go..
//			catch (\Exception $e) {
//				throw new CmsException('template.error.create_internal_error', null, $e);
//			}
		}

		// Keep created layout
		$this->layout = $layout;
	}

}
