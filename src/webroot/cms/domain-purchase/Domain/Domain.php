<?php

namespace Supra\Cms\DomainPurchase\Domain;

class Domain
{
	
	// domain levels
	const LEVEL_FQDN = 1001;
	const LEVEL_ALL = 1002;
	
	const LEVEL_TOP = 1;
	const LEVEL_SECOND = 2;
	const LEVEL_THIRD = 3;
	
	
	/**
	 * Contains list of domain names for specified domain item
	 * ex.: "www.example.com" will be presented as array of values
	 * array(3) {
	 *  [0]=> 
	 *		string(0) "" // root level, noname domain
	 *	[1]=>
	 *		string(3) "com"
	 *	[2]=>
	 *	    string(7) "example"
	 *	[3]=>
	 *		string(3) "www"
	 *	}
	 * 
	 * @var array
	 */
	protected $domainNames = array();
	
	/**
	 * Expiration date of domain name
	 * @var DateTime
	 */
	protected $expirationDate;
	
	/**
	 * List with domain records/properties
	 * @var array
	 */
	protected $records = array();
	
	public function addRecord($type, $value)
	{
		if ( ! in_array($type, $this->recordTypes)) {
			return false;
		}
		
		$this->records[$type] = $value;
		
		return true;
	}
	
	public function validate()
	{
		if (empty($this->domainNames)) {
			throw new ValidationException('Domain name is empty');
		}
		
	    foreach ($this->domainNames as $name) {
			if ( ! preg_match('/^[a-z\d][a-z\d-]{0,62}$/i', $name) || preg_match('/-$/', $name) ) {
				throw new ValidationException("Domain part {$name} is invalid");
			}
		}
	}
	
	public function getRecord($type)
	{
		if (isset($this->records[$type])) {
			return $this->records[$type];
		}
		
		return null;
	}
	
	public function modifyRecord($type, $value)
	{
		// check types
		// check isset
		
		$this->addRecord($type, $value);
	}
	
	/**
	 * Set domain name
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function setDomainName($name) 
	{
		$names = explode('.', $name);
		
		if (empty($names)) {
			return false;
		}
		
		// root level, noname domain
		array_unshift($names, '');
		
		$this->domainNames = $names;
		
		return true;
	}
	
	/**
	 * Return full/fqdn domain name, or specified level domain name
	 * 
	 * @param int $level
	 * @return string
	 */
	public function getDomainName($level = self::LEVEL_ALL)
	{
		if (empty($this->domainNames)) {
			return null;
		}
		
		// specific levels
		switch($level) {
			case self::LEVEL_FQDN:
				return implode('.', $this->domainNames);
				break;
			
			case self::LEVEL_ALL:
				return implode('.', array_slice($this->domainNames, 1));
				break;
		}
		
		// numeric levels
		if (isset($this->domainNames[$level])) {
			return $this->domainNames[$level];
		}
					
		return null;
	}
	
	/**
	 * Returns top level domain
	 * ex. "com", "lv", "ru" etc.
	 * @return string
	 */
	public function getTopLevelDomain()
	{
		return $this->getDomainName(self::LEVEL_TOP);
	}
	
	/**
	 * Returns full domain name
	 * ex. "www.example.com"
	 * @return string
	 */
	public function getFullDomainName()
	{
		return $this->getDomainName(self::LEVEL_ALL);
	}
	
	/**
	 * Fully qualified domain name(FQDN), contains root level domain
	 * ex. "subdomain.example.com."
	 * @return string
	 */
	public function getFullyQualifiedDomainName()
	{
		return $this->getDomainName(self::LEVEL_FQDN);
	}
		
}
