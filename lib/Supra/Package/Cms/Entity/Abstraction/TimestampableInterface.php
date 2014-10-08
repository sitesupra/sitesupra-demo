<?php

namespace Supra\Package\Cms\Entity\Abstraction;

/**
 * For entities with creation and modification time
 *
 * @TODO: this might be marked as deprecated,
 *   since we can use doctrine's @prePersist and @preUpdate annotations.
 */
interface TimestampableInterface
{
	public function getCreationTime();
	public function setCreationTime(\DateTime $time = null);
	public function getModificationTime();
	public function setModificationTime(\DateTime $time = null);
}
