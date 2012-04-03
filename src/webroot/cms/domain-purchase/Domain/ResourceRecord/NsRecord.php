<?php

namespace Supra\Cms\DomainPurchase\Domain\ResourceRecord;

class NsRecord extends ResourceRecordAbstract
{
	
	const TYPE = 'NS';
		
	/**
	 * [NSDNAME]
     *   a <domain-name> which specifies a host which should be authoritative 
	 *   for the specified class and domain.
	 * 
	 * @var array
	 */
	protected $rdataFields = array('NSDNAME');

}