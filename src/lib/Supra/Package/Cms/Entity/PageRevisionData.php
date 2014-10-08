<?php

namespace Supra\Package\Cms\Entity;

use Supra\Package\Cms\Entity\Abstraction\TimestampableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Supra\Controller\Pages\Exception;
use DateTime;

/**
 * Revision data class
 * @Entity
 * @Table(indexes={
 * 		@index(name="id_type_reference", columns={"id", "type", "reference"})
 * })
 */
class PageRevisionData extends Abstraction\Entity implements TimestampableInterface
{
	// Publish
	const TYPE_HISTORY = 1;
	
	const TYPE_HISTORY_RESTORE = 101;
	
	// page has been moved to trashbin
	const TYPE_TRASH = 2;
		
	// localization has beed restored
	// when page is restored from trash, revision data is marked as 'restored'
	// and will not be shown at recycle bin anymore
	const TYPE_RESTORED = 3;
	
	// one of the page elements has been edited
	const TYPE_ELEMENT_EDIT = 4;
	// one of the page elements has been deleted
	const TYPE_ELEMENT_DELETE = 5;
	
	// new localization has been created
	const TYPE_CREATE = 6;
	
	// localization has been duplicated
	const TYPE_DUPLICATE = 8;
	
	// 
	const TYPE_INSERT = 7;
	
	/**
	 * @Column(type="datetime", nullable=true, name="created_at")
	 * @var DateTime
	 */
	protected $creationTime;
	
	/**
	 * @Column(type="supraId20", nullable=true)
	 * @var string
	 */
	protected $user;
	
	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $type;
	
	/**
	 * Contains page or page localization ID
	 * 
	 * @Column(type="supraId20", nullable=true)
	 * @var string
	 */
	protected $reference;
	
	/**
	 * Contains page or page localization ID
	 * 
	 * @Column(type="supraId20", nullable=true)
	 * @var string
	 */
	protected $globalReference;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $elementName;
	
	/**
	 * Used to store block title
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $elementTitle;
	
	/**
	 * @Column(type="supraId20", nullable=true)
	 * @var string
	 */
	protected $elementId;
	
	/**
	 * @Column(type="text", nullable=true)
	 * @var sting
	 */
	protected $additionalInfo;
	
	/**
	 * Returns revision author
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}
	
	/**
	 * Sets revision author
	 * @param string $user 
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}
	
	/**
	 * Returns creation time
	 * @return DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}
	
	/**
	 * Sets creation time
	 * @param DateTime $time
	 */
	public function setCreationTime(DateTime $time = null)
	{
		if (is_null($time)) {
			$time = new DateTime('now');
		}
		$this->creationTime = $time;
	}
	
	/**
	 * Doesn't store modification time
	 * @return DateTime
	 */
	public function getModificationTime()
	{
		return null;
	}

	/**
	 * Doesn't store modification time
	 * @param DateTime $time
	 */
	public function setModificationTime(DateTime $time = null)
	{
		
	}

	/**
	 * @return integer
	 */
	public function getType()
	{
		return $this->type;
	}
	
	/**
	 * @param integer $type 
	 */
	public function setType($type)
	{
		$this->type = $type;
	}
	
	/**
	 * @return string
	 */
	public function getReferenceId()
	{
		return $this->reference;
	}
	
	/**
	 * @param string $referenceId 
	 */
	public function setReferenceId($referenceId)
	{
		$this->reference = $referenceId;
	}
	
	/**
	 * @param string $localizationId
	 */
	public function setGlobalElementReferenceId($referenceId)
	{
		$this->globalReference = $referenceId;
	}
	
	public function getGlobalElementReferenceId()
	{
		return $this->globalReference;
	}
	
	/**
	 * @param string $id
	 */
	public function setElementId($id) 
	{
		$this->elementId = $id;
	}
	
	/**
	 * @param string $name
	 */
	public function setElementName($name)
	{
		$this->elementName = $name;
	}
	
	/**
	 * @return string 
	 */
	public function getElementName()
	{
		return $this->elementName;
	}
	
	/**
	 * @return string
	 */
	public function getElementId()
	{
		return $this->elementId;
	}
	
	/**
	 * @param string $info
	 */
	public function setAdditionalInfo($info)
	{
		$this->additionalInfo = $info;
	}
	
	/**
	 * @return string
	 */
	public function getAdditionalInfo()
	{
		return $this->additionalInfo;
	}
	
	/**
	 * @return string
	 */
	public function getElementTitle()
	{
		return $this->elementTitle;
	}

	/**
	 * @param string $elementTitle
	 */
	public function setElementTitle($elementTitle)
	{
		$this->elementTitle = $elementTitle;
	}

}