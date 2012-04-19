<?php

namespace Supra\Loader\Strategy;

use Supra\Loader\Loader;
use Supra\Loader\Exception;
use Supra\ObjectRepository\ObjectRepository;
use Doctrine\Common\Util\ClassUtils;

class SupraProxyLoadStrategy extends NamespaceLoaderStrategy
{
	/**
	 * {@inheritdoc}
	 */
	public function convertToFilePath($classPath)
	{
		$path = str_replace('\\', '', $classPath) . '.php';
		
		return $path;
	}
	
	/**
	 * Additionally execute proxy class generation if no such file exists
	 * {@inheritdoc}
	 */
	public function findClass($className)
	{
		$fileName = parent::findClass($className);
		
		if (is_null($fileName)) {
			return null;
		}
		
		if ( ! file_exists($fileName)) {
			
			$entityName = ClassUtils::getRealClass($className);
			
			// Use the default EM for now
			$entityManager = ObjectRepository::getEntityManager('');
			$entityMetadata = $entityManager->getClassMetadata($entityName);
			
			if (empty($entityMetadata)) {
				\Log::warn("Metadata not found for entity $entityName inside doctrine entity manager");
				return null;
			}
			
			$entityManager->getProxyFactory()
					->generateProxyClasses(array($entityMetadata));
		}
		
		return $fileName;
	}
}
