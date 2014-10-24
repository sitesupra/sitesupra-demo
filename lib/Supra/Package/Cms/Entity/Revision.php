<?php

namespace Supra\Package\Cms\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Page/Template revision data class.
 *
 * @//Entity(readOnly=true)
 * @//Table(indexes={
 * 		@//index(name="id_type_reference", columns={"id","reference"})
 * })
 */
class Revision extends Abstraction\Entity
{
	/**
	 * Revision author username.
	 *
	 * @Column(type="string", nullable=true)
	 * @var string
	 */
	protected $author;

	/**
	 * @Column(type="datetime")
	 * @var \DateTime
	 */
	protected $creationTime;

	/**
	 * Short revision description.
	 *
	 * @Column(type="string")
	 * @var string
	 */
	protected $description;

	/**
	 * @param UserInterface $author
	 */
	public function __construct(UserInterface $author = null)
	{
		parent::__construct();

		if ($author) {
			$this->author = $author->getUsername();
		}
	}

	/**
	 * Returns revision author username.
	 * 
	 * @return string
	 */
	public function getAuthor()
	{
		return $this->author;
	}

	/**
	 * Returns revision creation time.
	 *
	 * @return \DateTime
	 */
	public function getCreationTime()
	{
		return $this->creationTime;
	}
}