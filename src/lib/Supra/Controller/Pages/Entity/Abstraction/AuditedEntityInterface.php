<?php
namespace Supra\Controller\Pages\Entity\Abstraction;

/**
 * Dummy interface
 * Entity with implementation of this interface will be audited by Audit listener
 */
interface AuditedEntityInterface
{
	const CN = __CLASS__;

	/**
	 * @return string
	 */
	public function getRevisionId();
}
