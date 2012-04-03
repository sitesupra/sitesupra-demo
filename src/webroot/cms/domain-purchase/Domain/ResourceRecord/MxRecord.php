<?php

namespace Supra\Cms\DomainPurchase\Domain\ResourceRecord;

class MxRecord extends ResourceRecordAbstract
{
	
	const TYPE = 'MX';
	
	/**
	 * [PREFERENCE]
	 *   a 16 bit integer which specifies the preference given to this RR
	 *   among others at the same owner.
	 *   Lower values are preferred.
     * [EXCHANGE]
     *   a <domain-name> which specifies a host willing to act
	 *   as a mail exchange for the owner name.
	 * @var array
	 */
	protected $rdataFields = array('PREFERENCE', 'EXCHANGE');
		
}