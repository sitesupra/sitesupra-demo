<?php

namespace Supra\Controller\Pages\Request;

use \Supra\Controller\Pages\Set;
use Doctrine\ORM\EntityManager;

/**
 * Special request for groups
 */
class GroupPageRequest extends PageRequestEdit
{
	public function getLayout()
	{
		return null;
	}
	
	public function getLayoutPlaceHolderNames()
	{
		return array();
	}
	
	public function publish()
	{
		return;
	}
	
	public function getBlockPropertySet()
	{
		return new Set\BlockPropertySet();
	}

	public function getBlockSet()
	{
		return new Set\BlockSet();
	}

	public function getPlaceHolderSet()
	{
		return new Set\PlaceHolderSet();
	}

	public function getRootTemplate()
	{
		return;
	}

	public function moveBetweenManagers(EntityManager $sourceEm, EntityManager $destEm, $pageDataId, $forceSave = false)
	{
		return;
	}

}
