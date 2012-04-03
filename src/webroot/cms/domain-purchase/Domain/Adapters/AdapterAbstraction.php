<?php

namespace Supra\Cms\DomainPurchase\Domain\Adapters;

use Supra\Cms\DomainPurchase\Domain\Domain;

abstract class AdapterAbstraction 
{

	abstract public function checkDomainAvailability(Domain $domain);
	
	abstract public function purchaseDomain(Domain $domain);
	
	abstract public function listAllDomainRecords(Domain $domain);
	
	abstract public function getDomainRecord(Domain $domain, $type);
	
	abstract public function modifyDomainRecord(Domain $domain, $type, $params);
	
}
