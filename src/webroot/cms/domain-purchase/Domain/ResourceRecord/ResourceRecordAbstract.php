<?php

namespace Supra\Cms\DomainPurchase\Domain\ResourceRecord;

abstract class ResourceRecordAbstract
{
	
	/**
	 * IN is commonly used,
	 * still, two additional classes exist - CH (Chaos) and HS(Hesoid)
	 */
	const CLASS_IN = 'IN';
	const CLASS_CH = 'CH';
	const CLASS_HS = 'HS';

	/**
	 * Contains domain object which this record is mapped to
	 * @var Domain
	 */
	protected $domain;
	
	/**
	 * FQDN of domain, extracted from Domain object
	 * @var string
	 */
	protected $name;
	
	/**
	 * Class of record ("IN", "CH" or "HS")
	 * "IN" is most used class
	 * @var string
	 */
	protected $class;
	
	/**
	 * Resource record Time To Live, in seconds
	 * Shouldn't be less than zero and greater than (2^31 - 1)
	 * @var int
	 */
	protected $ttl;
	
	/**
	 * Additional record data
	 * as example - canonical domain nam "subdomain.example.com." of CNAME record
	 * should be passed as RDATA value
	 * @var string
	 */	
	protected $rdata;
	
	/**
	 * Length of RDATA
	 * @var int
	 */
	protected $rdlength;
	
		
	public function getType()
	{
		return self::TYPE;
	}
	
	/**
	 * Set record related domain
	 * @param Domain $domain
	 */
	public function setDomain(Domain $domain)
	{
		$this->domain = $domain;
		$name = $domain->getFullyQualifiedDomainName();

		if ( ! is_null($name)) {
			$this->name = $name;
		}
	}
	
	/**
	 * Set record CLASS code
	 * ex. "IN" or "EX", 
	 * @param string $class 
	 */
	public function setClass($class)
	{
		$this->class = $class;
	}
	
	/**
	 * Get record CLASS code
	 * @return string
	 */
	public function getClass()
	{
		return $this->class;
	}
	
	/**
	 * Set record TTL (in seconds)
	 * @param int $ttl 
	 */
	public function setTtl($ttl)
	{
		if ($ttl < 0) {
			throw new RuntimeException('TTL cannot be negative');
		} else if ($ttl > 2147483647) {
			throw new RuntimeException('TTL maximum value should be less than "(2^31)-1"');			
		}
		
		$this->ttl = $ttl;
	}
	
	/**
	 * Get TTL
	 * @return int
	 */
	public function getTtl()
	{
		return $this->ttl;
	}
	
	/**
	 * Set additional record data and calculate it's length
	 * @param string $rdata
	 */
	public function setRdata($rdata)
	{
		$this->rdlength = mb_strlen($rdata);
		$this->rdata = $rdata;
	}
	
	/**
	 * Get resource record RDATA
	 * @return string
	 */
	public function getRdata()
	{
		return $this->rdata;
	}
	
	/**
	 * Get resource record RDLENGTH (length of RDATA)
	 * @return int
	 */
	public function getRdlength()
	{
		return $this->rdlength;
	}
			
}
