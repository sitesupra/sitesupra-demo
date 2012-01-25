<?php

namespace Supra\Console\Cron\Entity;

use Supra\Database\Entity;

/**
 * Cron job entity
 *
 * @Entity(repositoryClass="Supra\Console\Cron\Repository\CronJobRepository")
 * @Table(name="su_cron_job",indexes={@index(name="next_execution_time_idx", columns={"next_execution_time"})})
 */
class CronJob extends Entity
{

	const
		STATUS_OK = 0,
		STATUS_NEW = 1,
		STATUS_LOCKED = 2,
		STATUS_FAILED = 3,
		STATUS_MASTER = 10;

	/**
	 * @Column(name="command_input",type="string",nullable=true)
	 * @var string
	 */
	protected $commandInput;

	/**
	 * @Column(name="period_class",type="string",nullable=true)
	 * @var string 
	 */
	protected $periodClass;

	/**
	 * @Column(name="period_parameter",type="string",nullable=true)
	 * @var string
	 */
	protected $periodParameter;

	/**
	 * @Column(name="last_execution_time",type="datetime",nullable=true)
	 * @var DateTime
	 */
	protected $lastExecutionTime;

	/**
	 * @Column(name="next_execution_time",type="datetime",nullable=true)
	 * @var type 
	 */
	protected $nextExecutionTime;

	/**
	 * @Column(type="integer",nullable=false)
	 * @var integer
	 */
	protected $status = self::STATUS_NEW;

	/**
	 * Set command input string
	 *
	 * @param string $commandInput 
	 */
	public function setCommandInput($commandInput)
	{
		$this->commandInput = $commandInput;
	}

	/**
	 * Get command input string
	 *
	 * @return string
	 */
	public function getCommandInput()
	{
		return $this->commandInput;
	}

	/**
	 * Set last command execution time
	 *
	 * @param \DateTime $time 
	 */
	public function setLastExecutionTime(\DateTime $time)
	{
		$this->lastExecutionTime = $time;
	}

	/**
	 * Get last command execution time
	 *
	 * @return \DateTime
	 */
	public function getLastExecutionTime()
	{
		return $this->lastExecutionTime;
	}

	/**
	 * Set next command execution time
	 *
	 * @param \DateTime $time 
	 */
	public function setNextExecutionTime(\DateTime $time)
	{
		$this->nextExecutionTime = $time;
	}

	/**
	 * Get next command execution time
	 *
	 * @return \DateTime
	 */
	public function getNextExecutionTime()
	{
		return $this->nextExecutionTime;
	}

	/**
	 * Set execution time period class name
	 *
	 * @param string $periodClass 
	 */
	public function setPeriodClass($periodClass)
	{
		$this->periodClass = $periodClass;
	}

	/**
	 * Get execution time period class name
	 *
	 * @return string
	 */
	public function getPeriodClass()
	{
		return $this->periodClass;
	}

	/**
	 * Set execution time period parameter
	 *
	 * @param string $periodParameter 
	 */
	public function setPeriodParameter($periodParameter)
	{
		$this->periodParameter = $periodParameter;
	}

	/**
	 * Get execution time period parameter
	 *
	 * @return string
	 */
	public function getPeriodParameter()
	{
		return $this->periodParameter;
	}


	/**
	 * Status
	 *
	 * @param integer $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}

	/**
	 * Get status
	 *
	 * @return integer
	 */
	public function getStatus()
	{
		return $this->status;
	}

}
