<?php

namespace Supra\Database\Doctrine\Cache;

use Supra\Cache\CacheNamespaceWrapper;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\ORM\Proxy\ProxyFactory;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * This metadata cache implementation will regenerate proxy classes when entity
 * metadata is stored in the cache.
 */
class ProxyFactoryMetadataCache extends CacheNamespaceWrapper
{

	/**
	 * @var boolean
	 */
	protected $writeOnlyMode = false;

	/**
	 * @return boolean
	 */
	public function getWriteOnlyMode()
	{
		return $this->writeOnlyMode;
	}

	/**
	 * @param boolean $writeOnlymode
	 */
	public function setWriteOnlyMode($writeOnlymode)
	{
		$this->writeOnlyMode = $writeOnlymode;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function doContains($id)
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	protected function doFetch($id)
	{
		if ( ! $this->writeOnlyMode) {
			return parent::doFetch($id);
		} else {
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	protected function doSave($id, $classMetadata, $lifeTime = false)
	{
		if ($classMetadata instanceof ClassMetadata) {
			$namespace = $this->getNamespace();
			$em = ObjectRepository::getEntityManager($namespace);
			$proxyFactory = $em->getProxyFactory();
			$proxyFactory->generateProxyClasses(array($classMetadata));
		}

		return parent::doSave($id, $classMetadata, $lifeTime);
	}

}
