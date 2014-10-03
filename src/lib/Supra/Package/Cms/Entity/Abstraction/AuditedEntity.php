<?php

namespace Supra\Package\Cms\Entity\Abstraction;

interface AuditedEntity extends VersionedEntityInterface
{
	const AUDITED_ENTITY_INTERFACE = __CLASS__;
}
