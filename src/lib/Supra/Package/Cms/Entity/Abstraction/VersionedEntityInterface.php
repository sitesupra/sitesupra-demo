<?php

namespace Supra\Package\Cms\Entity\Abstraction;

/**
 * Draft/Public model versioned entityinterface.
 */
interface VersionedEntityInterface
{
	const VERSIONED_ENTITY_INTERFACE = __CLASS__;

	/**
	 * Must return versioned entity revision.
	 *
	 * @return string
	 */
	public function getRevision();

	/**
	 * Setter for versioned entity revision.
	 *
	 * @param string $revision
	 */
	public function setRevision($revision);
}