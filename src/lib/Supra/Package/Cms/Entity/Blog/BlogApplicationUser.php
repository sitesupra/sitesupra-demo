<?php

namespace Supra\Package\Cms\Entity\Blog;

use Supra\User\Entity\User;
use Supra\Package\Cms\Entity\Abstraction\Entity;

/**
 * Blog application user class
 * Exists as storage for Blog's "About author" field
 * 
 * @Entity
 */
class BlogApplicationUser extends Entity
{
	/**
	 * @Column(type="supraId20")
	 * @var string 
	 */
	protected $supraUserId;
	
	/**
	 * @Column(type="string", nullable=true)
	 * @var string 
	 */
	protected $name;
	
	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $about;
		
	/**
	 * @Column(type="text", nullable=true)
	 * @var string
	 */
	protected $avatar;
	
	/**
	 * @param string $name
	 */
	public function __construct(User $supraUser)
	{
		parent::__construct();
		
		$this->supraUserId = $supraUser->getId();
		$this->name = $supraUser->getName();
		
		$this->avatar = $supraUser->getGravatarUrl();
	}
	
	/**
	 * @return string
	 */
	public function getSupraUserId()
	{
		return $this->supraUserId;
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
	 * Getter for "About author" text
	 * @return string
	 */
	public function getAboutText()
	{
		return $this->about;
	}
	
	/**
	 * Setter for "About author" text
	 * @param string $aboutText
	 */
	public function setAboutText($aboutText)
	{
		$this->about = $aboutText;
	}
	
	/**
	 *
	 * @return string
	 */
	public function getAvatar()
	{
		return $this->avatar;
	}
	
	/**
	 *
	 * @param string $avatar
	 */
	public function setAvatar($avatar)
	{
		$this->avatar = $avatar;
	}
}
