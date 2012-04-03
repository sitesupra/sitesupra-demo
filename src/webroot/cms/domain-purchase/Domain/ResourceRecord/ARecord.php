<?php

namespace Supra\Cms\DomainPurchase\Domain\ResourceRecord;

class ARecord extends ResourceRecordAbstract
{
	
	const TYPE = 'A';
		
	/**
	 * [ADDRESS]
     *   a 32 bit Internet address
	 * 
	 * @var array
	 */
	protected $rdataFields = array('ADDRESS');

}