<?php

namespace Supra\Form;

use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\Validator\Exception\MappingException;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\Constraints\GroupSequenceProvider;
use Symfony\Component\Validator\Constraint;
use ReflectionClass;

/**
 * Overriden annotation loader to be able to overwrite the constraint messages
 */
class FormAnnotationLoader extends AnnotationLoader
{
	private $cache = array();

	private $reflClasses= array();

	/**
	 * @param \Doctrine\Common\Annotations\Reader $reader
	 */
	public function __construct(Reader $reader = null)
    {
		if (is_null($reader)) {
			$reader = new AnnotationReader();
		}
        parent::__construct($reader);
    }

	/**
	 * @param string $className
	 * @return ReflectionClass
	 */
	public function getReflectionClass($className)
	{
		if ( ! isset($this->reflClasses[$className])) {
			$this->reflClasses[$className] = new ReflectionClass($className);
		}
		
		return $this->reflClasses[$className];
	}

	/**
	 * @param string $className
	 * @return array
	 */
	public function getClassAnnotations($className)
	{
		if ( ! isset($this->cache[$className]['class'])) {
			$reflClass = $this->getReflectionClass($className);
			$this->cache[$className]['class'] = $this->reader->getClassAnnotations($reflClass);
		}

		return $this->cache[$className]['class'];
	}

	/**
	 * @param string $className
	 * @return array
	 */
	public function getPropertyAnnotations($className)
	{
		if ( ! isset($this->cache[$className]['properties'])) {
			$reflClass = $this->getReflectionClass($className);
			$this->cache[$className]['properties'] = array();

			foreach ($reflClass->getProperties() as $property) {
				if ($property->getDeclaringClass()->name == $className) {
					$this->cache[$className]['properties'][$property->name] = $this->reader->getPropertyAnnotations($property);
				}
			}
		}

		return $this->cache[$className]['properties'];
	}

	/**
	 * @param string $className
	 * @return array
	 */
	public function getMethodAnnotations($className)
	{
		if ( ! isset($this->cache[$className]['methods'])) {
			$reflClass = $this->getReflectionClass($className);
			$this->cache[$className]['methods'] = array();

			foreach ($reflClass->getMethods() as $method) {
				if ($method->getDeclaringClass()->name ==  $className) {
					$this->cache[$className]['methods'][$method->name] = $this->reader->getMethodAnnotations($method);
				}
			}
		}

		return $this->cache[$className]['methods'];
	}

	/**
     * {@inheritDoc}
     */
    public function loadClassMetadata(ClassMetadata $metadata)
    {
        $className = $metadata->name;
        $loaded = false;

		$classConstraints = $this->getClassAnnotations($className);
		$propertyConstraints = $this->getPropertyAnnotations($className);
		$methodConstraints = $this->getMethodAnnotations($className);

		if ( ! isset($this->reflClasses[$className])) {
			$this->reflClasses[$className] = $metadata->getReflectionClass();
		}

        foreach ($classConstraints as $constraint) {
            if ($constraint instanceof GroupSequence) {
                $metadata->setGroupSequence($constraint->groups);
            } elseif ($constraint instanceof GroupSequenceProvider) {
                $metadata->setGroupSequenceProvider(true);
            } elseif ($constraint instanceof Constraint) {
                $metadata->addConstraint($constraint);
            }

            $loaded = true;
        }

        foreach ($propertyConstraints as $name => $constraints) {
			foreach ($constraints as $constraint) {
				if ($constraint instanceof Constraint) {
					$metadata->addPropertyConstraint($name, $constraint);
				}

				$loaded = true;
			}
        }

        foreach ($methodConstraints as $name => $constraints) {
			foreach ($constraints as $constraint) {
				if ($constraint instanceof Constraint) {
					if (preg_match('/^(get|is)(.+)$/i', $name, $matches)) {
						$metadata->addGetterConstraint(lcfirst($matches[2]), $constraint);
					} else {
						throw new MappingException(sprintf('The constraint on "%s::%s" cannot be added. Constraints can only be added on methods beginning with "get" or "is".', $className, $name));
					}
				}

				$loaded = true;
			}
        }

        return $loaded;
    }
}
