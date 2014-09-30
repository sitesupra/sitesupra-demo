<?php

namespace Supra\Package\Cms\Entity\Abstraction;

interface AuditedEntity
{
	const AUDITED_ENTITY_INTERFACE = __CLASS__;

	/**
	 * @return string
	 */
	public function getRevisionId();
}
