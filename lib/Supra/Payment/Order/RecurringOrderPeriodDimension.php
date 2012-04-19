<?php

namespace Supra\Payment\Order;

class RecurringOrderPeriodDimension
{
	const MONTH = 1;
	const WEEK = 2;
	const DAY = 3;
	
	/**
	 * @param integer $periodDimension 
	 */
	static function validate($periodDimension)
	{
		$konwnPeriodDimensions = array(
			self::MONTH,
			self::WEEK,
			self::DAY
		);

		if ( ! in_array($periodDimension, $konwnPeriodDimensions)) {
			throw new Exception\RuntimeException('Unkown period dimesnion value. User constants from RecurringOrderPeriodDimesion class.');
		}
		
		return true;
	}

}
