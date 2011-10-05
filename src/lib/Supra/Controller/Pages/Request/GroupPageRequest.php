<?php

namespace Supra\Controller\Pages\Request;

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
	
	public function publish(EntityManager $publicEm)
	{
		return;
	}
	
	public function getBlockPropertySet()
	{
		return new \Supra\Controller\Pages\Set\BlockPropertySet();
	}

	public function getBlockSet()
	{
		return new \Supra\Controller\Pages\Set\BlockSet();
	}

	public function getPlaceHolderSet()
	{
		return new \Supra\Controller\Pages\Set\PlaceHolderSet();
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
