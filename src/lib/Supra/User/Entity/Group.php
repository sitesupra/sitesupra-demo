<?php

namespace Supra\User\Entity;

/**
 * Group object
 * @Entity
 * @Table(name="`group`") 
 */
class Group extends Abstraction\User
{
	
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue
	 * @var integer
	 */
	protected $id = null;		
	
	/**
	 * Returns group id 
	 * @return integer 
	 */
	public function getId()
	{
		return $this->id;
	}


}