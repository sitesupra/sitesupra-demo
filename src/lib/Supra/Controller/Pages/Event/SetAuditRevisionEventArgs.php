<?php

namespace Supra\Controller\Pages\Event;

use Doctrine\Common\EventArgs;

/**
 * Passed when revision is set for audit manager
 */
class SetAuditRevisionEventArgs extends EventArgs
{
	/**
	 * @var string
	 */
	private $revision;

	/**
	 * @param string $revision
	 */
	public function __construct($revision)
	{
		$this->revision = $revision;
	}

	/**
	 * @return string
	 */
	public function getRevision()
	{
		return $this->revision;
	}
}
