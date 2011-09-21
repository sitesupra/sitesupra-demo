<?php

namespace Supra\User\Entity\Abstraction;

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"user" = "Supra\User\Entity\User", "group" = "Supra\User\Entity\Group"})
 * @Table(name="user_abstraction", indexes={
 *		@index(name="user_abstraction_name_idx", columns={"name"})
 * })
 * @HasLifecycleCallbacks
 */
class User
{
	
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id = null;
	
	/**
	 * @Column(type="string", name="name", nullable=false)
	 * @var string
	 */
	protected $name;
	
	/**
	 * @Column(type="datetime", name="created_at")
	 * @var string
	 */
	protected $createdTime;
	
	/**
	 * @Column(type="datetime", name="modified_at")
	 * @var string
	 */
	protected $modifiedTime;
	
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * Returns creation time
	 * @return \DateTime
	 */
	public function getCreatedTime()
	{
		return $this->createdTime;
	}
	
	/**
	 * Sets creation time to now
	 * @PrePersist
	 */
	public function setCreatedTime()
	{
		$this->createdTime = new \DateTime('now');
	}

	/**
	 * Returns last modification time
	 * @return \DateTime
	 */
	public function getModifiedTime()
	{
		return $this->modifiedTime;
	}

	/**
	 * Sets modification time to now 
	 * @PreUpdate
	 * @PrePersist
	 */
	public function setModifiedTime()
	{
		$this->modifiedTime = new \DateTime('now');
	}

}