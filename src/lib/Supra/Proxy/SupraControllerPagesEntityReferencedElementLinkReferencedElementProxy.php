<?php

namespace Supra\Proxy;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class SupraControllerPagesEntityReferencedElementLinkReferencedElementProxy extends \Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }
    
    
    public function getResource()
    {
        $this->__load();
        return parent::getResource();
    }

    public function setResource($resource)
    {
        $this->__load();
        return parent::setResource($resource);
    }

    public function getHref()
    {
        $this->__load();
        return parent::getHref();
    }

    public function setHref($href)
    {
        $this->__load();
        return parent::setHref($href);
    }

    public function getTarget()
    {
        $this->__load();
        return parent::getTarget();
    }

    public function setTarget($target)
    {
        $this->__load();
        return parent::setTarget($target);
    }

    public function getTitle()
    {
        $this->__load();
        return parent::getTitle();
    }

    public function setTitle($title)
    {
        $this->__load();
        return parent::setTitle($title);
    }

    public function getPageId()
    {
        $this->__load();
        return parent::getPageId();
    }

    public function setPageId($pageId)
    {
        $this->__load();
        return parent::setPageId($pageId);
    }

    public function getFileId()
    {
        $this->__load();
        return parent::getFileId();
    }

    public function setFileId($fileId)
    {
        $this->__load();
        return parent::setFileId($fileId);
    }

    public function toArray()
    {
        $this->__load();
        return parent::toArray();
    }

    public function fillArray(array $array)
    {
        $this->__load();
        return parent::fillArray($array);
    }

    public function getUrl($context)
    {
        $this->__load();
        return parent::getUrl($context);
    }

    public function getDiscriminator()
    {
        $this->__load();
        return parent::getDiscriminator();
    }

    public function matchDiscriminator(\Supra\Controller\Pages\Entity\Abstraction\Entity $object)
    {
        $this->__load();
        return parent::matchDiscriminator($object);
    }

    public function authorize(\Supra\User\Entity\Abstraction\User $user, $permission, $grant)
    {
        $this->__load();
        return parent::authorize($user, $permission, $grant);
    }

    public function getAuthorizationId()
    {
        $this->__load();
        return parent::getAuthorizationId();
    }

    public function getAuthorizationClass()
    {
        $this->__load();
        return parent::getAuthorizationClass();
    }

    public function getAuthorizationAncestors()
    {
        $this->__load();
        return parent::getAuthorizationAncestors();
    }

    public function setRevisionData($revisionData)
    {
        $this->__load();
        return parent::setRevisionData($revisionData);
    }

    public function getRevisionData()
    {
        $this->__load();
        return parent::getRevisionData();
    }

    public function getId()
    {
        $this->__load();
        return parent::getId();
    }

    public function equals(\Supra\Database\Entity $entity)
    {
        $this->__load();
        return parent::equals($entity);
    }

    public function __toString()
    {
        $this->__load();
        return parent::__toString();
    }

    public function getProperty($name)
    {
        $this->__load();
        return parent::getProperty($name);
    }


    public function __sleep()
    {
        return array('__isInitialized__', 'id', 'resource', 'href', 'target', 'title', 'pageId', 'fileId');
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields AS $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        
    }
}