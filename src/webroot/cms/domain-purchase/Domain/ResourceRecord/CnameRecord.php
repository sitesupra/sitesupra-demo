<?php

namespace Supra\Cms\DomainPurchase\Domain\ResourceRecord;

class CnameRecord extends ResourceRecordAbstract
{
	
	const TYPE = 'CNAME';
	
	/**
	 * [CNAME]
     *   a <domain-name> which specifies the canonical or primary name for the owner.
	 *   The owner name is an alias.
	 * 
	 * @var array
	 */
	protected $rdataFields = array('CNAME');
	
}