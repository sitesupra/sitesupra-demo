<?php

namespace Supra\Package\Cms\Entity\Abstraction;

/**
 * @MappedSuperclass
 */
abstract class VersionedEntity extends Entity implements VersionedEntityInterface
{
	/**
	 * Keeps the entity revision.
	 *
	 * @Column(type="supraId20", nullable=true)
	 *
	 * @var string
	 */
	protected $revision;

	/**
	 * @var int
	 */
	protected $revisionType;

	/**
	 * @inheritDoc
	 */
	public function getRevision()
	{
		return $this->revision;
	}

	/**
	 * @inheritDoc
	 */
	public function setRevision($revision)
	{
		$this->revision = $revision;
	}

	/**
	 * @inheritDoc
	 */
	public function getVersionedParent()
	{
		return null;
	}
}
