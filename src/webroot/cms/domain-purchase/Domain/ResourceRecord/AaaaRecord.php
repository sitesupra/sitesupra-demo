<?php

namespace Supra\Cms\DomainPurchase\Domain\ResourceRecord;

class AaaaRecord extends ResourceRecordAbstract
{
	
	const TYPE = 'AAAA';
	
	/**
	 * [ADDRESS]
     *   IPv6 internet address
	 * 
	 * @var array
	 */
	protected $rdataFields = array('ADDRESS');
	
}