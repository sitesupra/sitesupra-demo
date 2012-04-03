<?php

namespace Supra\Cms\DomainPurchase\Domain\ResourceRecord;

class NullRecord extends ResourceRecordAbstract
{
	
	const TYPE = 'NULL';
	
	/**
	 * Anything at all may be in the Null record 
	 * RDATA field so long as it is 65535 octets
     * or less.
	 * 
	 * @var array
	 */
	protected $rdataFields = array('ANYTHING');
	
	public static function factory($type)
	{
		switch ($type) {
			case 'MX':
				return new MxRecord();
				break;
			
			case 'A':
				return new ARecord();
				break;
			
			case 'AAAA':
				return new AaaaRecord();
				break;
			
			case 'CNAME':
				return new CnameRecord();
				break;
			
			case 'NS':
				return new NsRecord();
				break;
			
			default:
				return new self();
		}
	}
		
}