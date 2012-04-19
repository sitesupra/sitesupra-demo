<?php

namespace Supra\Console\Cron\Repository;

use \Supra\Console\Cron\Entity\CronJob;
use \Supra\Console\Cron\Period\AbstractPeriod;

/**
 * CronJobRepository
 *
 */
class CronJobRepository extends \Doctrine\ORM\EntityRepository
{

	/**
	 * Add cron job
	 *
	 * @param string $commandInput
	 * @param AbstractPeriod $period 
	 */
	public function addJob($commandInput, AbstractPeriod $period)
	{
		$job = $this->findOneByCommandInput($commandInput);

		$periodClass = get_class($period);
		
		if ( ! $job instanceof CronJob) {
			$job = new CronJob();
			$this->_em->persist($job);
			$job->setCommandInput($commandInput);
			$job->setPeriodClass($periodClass);
			$job->setPeriodParameter($period->getParameter());
			$job->setNextExecutionTime($period->getNext());
		} else {
//			$job->setPeriodClass($periodClass);
//			$job->setPeriodParameter($period->getParameter());
//			$job->setNextExecutionTime($period->getNext());
		}

		$this->_em->flush();
	}

	/**
	 * Find jobs scheduled in a specified time range
	 *
	 * @param \DateTime $startTime
	 * @param \DateTime $endTime 
	 * @return array
	 */
	public function findScheduled($startTime, $endTime)
	{
		$qb = $this->_em->createQueryBuilder();
		$qb->select('cj')
				->from('Supra\Console\Cron\Entity\CronJob', 'cj')
				->where('cj.nextExecutionTime > :start')
				->andWhere('cj.nextExecutionTime <= :end')
				->setParameter('start', $startTime)
				->setParameter('end', $endTime)
				;
		$result = $qb->getQuery()->getResult();
		
		return $result;
	}
	
}
