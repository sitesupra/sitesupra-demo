<?php

namespace Supra\Database\Doctrine\Listener;

use DateTime;

/**
 * For entities with creation and modification time
 */
interface Timestampable
{
	public function getCreationTime();
	public function setCreationTime(DateTime $time = null);
	public function getModificationTime();
	public function setModificationTime(DateTime $time = null);
}
