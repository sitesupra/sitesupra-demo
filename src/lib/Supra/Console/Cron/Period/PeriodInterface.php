<?php

namespace Supra\Console\Cron\Period;

/**
 * PeriodInterface
 *
 */
interface PeriodInterface 
{

	public function __construct($parameter);

	public function getNext();

	public function getParameter();

}
