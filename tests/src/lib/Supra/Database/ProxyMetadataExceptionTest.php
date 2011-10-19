<?php

namespace Supra\Tests\Database;

/**
 * This test checks Doctrine problem existance that it cannot load correct 
 */
class ProxyMetadataExceptionTest extends \PHPUnit_Framework_TestCase
{
	const PROXY_NAME = 'Supra\Proxy\PublicSchema\SupraConsoleCronEntityCronJobProxy';
	
	public function testNotLoaded()
	{
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('');
		
		// Unsets metadata
		$em->getMetadataFactory()->setMetadataFor(self::PROXY_NAME, null);
		
		$this->load($em);
	}
	
	public function testLoaded()
	{
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('');
		
		$proxy = $em->getProxyFactory()->getProxy(\Supra\Console\Cron\Entity\CronJob::CN(), -1);
		
		$this->load($em);
	}
	
	private function load($em)
	{
		$metadata = null;
		
		try {
			$metadata = $em->getClassMetadata(self::PROXY_NAME);
		} catch (\Doctrine\ORM\Mapping\MappingException $e) {
			self::fail("Could not load metadata for the proxy class");
		}
		
		self::assertNotEmpty($metadata);
		
		self::assertEquals('Supra\Console\Cron\Entity\CronJob', $metadata->getName());
	}
}
