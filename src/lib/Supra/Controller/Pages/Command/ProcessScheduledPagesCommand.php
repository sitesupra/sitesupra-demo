<?php

namespace Supra\Controller\Pages\Command;

use Symfony\Component\Console\Command\Command;
use Supra\ObjectRepository\ObjectRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Supra\Controller\Pages\PageController;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Controller\Pages\Entity\PageLocalization;
use Supra\Controller\Pages\Event;

/**
 *
 */
class ProcessScheduledPagesCommand extends Command
{
	/**
	 * @var Doctrine\ORM\EntityManager
	 */
	private $_em;
	
	/**
     * Configures the current command.
     */
    protected function configure()
    {
		$this->setName('su:pages:process_scheduled')
				->setDescription("Publishes scheduled pages");
    }
	
	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_em = ObjectRepository::getEntityManager(PageController::SCHEMA_DRAFT);
		$publicEm = ObjectRepository::getEntityManager(PageController::SCHEMA_PUBLIC);
	
		$scheduledLocalizations = $this->findScheduled();
		
		$events = array();
		
		foreach ($scheduledLocalizations as $localization) {
			$request = PageRequestEdit::factory($localization);
			$request->setDoctrineEntityManager($this->_em);
			
			//$publicEm->getConnection()->beginTransaction();
			try {
				
				$request->publish();
				
				$eventArgs = new Event\PageCmsEventArgs($this);
				$eventArgs->localization = $localization;
				
				// Keep for triggers after flush
				$events[] = $eventArgs;
				
				//$publicEm->getConnection()->commit();
			} catch (\Exception $e) {

				//$publicEm->getConnection()->rollBack();
				// skip page, if something went wrong
				$pageId = $localization->getId();

				$log = ObjectRepository::getLogger($this);
				$log->error("Failed to publish localization #{$pageId}, with error {$e->getMessage()}");

				continue;

			}
			
			/* @var $localization PageLocalization */
			$localization->unsetScheduleTime();
			
		}
		$this->_em->flush();
		
		$eventManager = ObjectRepository::getEventManager($this);
		
		// Trigger post publish events
		foreach ($events as $eventArgs) {
			$eventManager->fire(Event\PageCmsEvents::pagePostPublish, $eventArgs);
		}
    }
	
	/**
	 * Helper method
	 * @return array
	 */
	private function findScheduled()
	{
		$qb = $this->_em->createQueryBuilder();
		$qb->select('l')
				->from(PageLocalization::CN(), 'l')
				->where('l.scheduleTime <= CURRENT_TIMESTAMP()')
				->andWhere('l.lock IS NULL')
				;
		$result = $qb->getQuery()->getResult();
		
		return $result;
	}
	
}
