<?php

namespace Supra\Cms\ContentManager\Blocks;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Request;
use Supra\Response;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Configuration\Exception\ConfigurationMissing;
use Supra\Controller\Pages\Configuration\BlockPropertyConfiguration;
use Supra\Controller\Pages\BlockPropertyGroupCollection;

class BlocksAction extends PageManagerAction
{
	/**
	 * Overriden so PHP <= 5.3.2 doesn't treat blocksAction() as a constructor
	 */
	public function __construct()
	{
		parent::__construct();
	}
	
	/**
	 * Collects block definition information
	 */
	public function blocksAction()
	{
		$logger = ObjectRepository::getLogger($this);
		$blockCollection = BlockControllerCollection::getInstance();

		$response = array(
			'blocks' => array(),
			'groups' => array(),
		);

		$isDefaultGroupSet = false;
		$defaultGroupKey = 0;
		$groupIds = array();
		
		$groupConfigurationList = $blockCollection->getGroupsConfigurationList();
		
		foreach ($groupConfigurationList as $group) {
			/* @var $group \Supra\Controller\Pages\Configuration\BlockControllerGroupConfiguration */

			$isDefaultGroup = $group->default;
			if ($isDefaultGroupSet && $isDefaultGroup) {
				$logger->warn("Default group is already set to \"{$response['groups'][$defaultGroupKey]['title']}\". Current group \"{$group->title}\" will be set as secondary");
				$isDefaultGroup = false;
			}

			if ($isDefaultGroup && ! $isDefaultGroupSet) {
				$newDefaultGroupKey = end(array_keys($response['groups']));

				if ( ! is_null($newDefaultGroupKey)) {
					$defaultGroupKey = $newDefaultGroupKey ++;
				}

				$isDefaultGroupSet = true;
			}

			$response['groups'][] = array(
				'id' => $group->id,
				'title' => $group->title,
				'default' => $isDefaultGroup,
			);

			$groupIds[] = $group->id;
		}

		if (empty($response['groups'])) {
			$response['groups'][] = array(
				'id' => 'all_blocks',
				'title' => 'All Blocks',
			);
		}

		if ( ! $isDefaultGroupSet) {
			$response['groups'][$defaultGroupKey]['default'] = true;
		}
		
		// Title array for ordering
		$titles = array();

		$blockConfigurationList = $blockCollection->getBlocksConfigurationList();
		
		foreach ($blockConfigurationList as $conf) {
			/* @var $conf \Supra\Controller\Pages\Configuration\BlockControllerConfiguration */

			$blockGroup = $conf->groupId;
			if (empty($blockGroup) || ! in_array($blockGroup, $groupIds)) {
				$logger->debug("Block \"{$conf->id}#{$conf->title}\" has empty group id or there is no group with such id. Block will be added into default group");
				$blockGroup = $response['groups'][$defaultGroupKey]['id'];
			}

			$controller = $blockCollection->getBlockController($conf->class);
			$propertyDefinition = $conf->properties;

			$properties = array();

			foreach ($propertyDefinition as $property) {
				/* @var $property BlockPropertyConfiguration */
				$editable = $property->editableInstance;
				
				$properties[] = array(
					'id' => $property->name,
					'type' => $editable->getEditorType(),
					'inline' => $editable->isInlineEditable(),
					'label' => $editable->getLabel(),
					'value' => $editable->getDefaultValue(),
					'group' => $editable->getGroupId(),
				) 
				+ $editable->getAdditionalParameters();
				
			}

			$response['blocks'][] = array(
				'id' => $conf->id,
				'group' => $blockGroup,
				'property_groups' => $conf->propertyGroups,
				'title' => $conf->title,
				'description' => $conf->description,
				'icon' => $conf->iconWebPath,
				'classname' => $conf->cmsClassname,
				'properties' => $properties,
				'hidden' => $conf->hidden,
				'html' => $conf->html,
			);
			
			$titles[] = $conf->title;
		}
		
		// Order by block title
		array_multisort($titles, $response['blocks']);

		$this->getResponse()->setResponseData($response);
	}

}