<?php

namespace Supra\Cms\ContentManager\Blocks;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\BlockControllerCollection;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Editable;

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

				$keys = array_keys($response['groups']);
				$newDefaultGroupKey = end($keys);

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

			$controller = $blockCollection->createBlockController($conf->class);
			$propertyDefinition = $conf->properties;

			$properties = $this->gatherPropertyArray($propertyDefinition);

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

		// Appends Theme hidden blocks
		$themeBlocksData = $this->getThemeBlocksData();
		if ( ! empty($themeBlocksData)) {
			array_push($response['blocks'], $themeBlocksData);
		}
		
		$this->getResponse()->setResponseData($response);
	}

	protected function gatherPropertyArray($properties)
	{
		$localeId = $this->getLocale()->getId();
		$response = array();

		if (is_array($properties)) {
			foreach ($properties as $property) {

				$editable = $property->editableInstance;

				$propertyData = array(
					'id' => $property->name,
					'type' => $editable->getEditorType(),
					'inline' => $editable->isInlineEditable(),
					'label' => $editable->getLabel(),
					'value' => $editable->getDefaultValue($localeId),
					'group' => $editable->getGroupId(),
						)
						+ $editable->getAdditionalParameters();

				if ($editable instanceof Editable\Gallery) {
					$propertyData['properties'] = $this->gatherPropertyArray($property->properties);
				}

				$response[] = $propertyData;
			}
		}

		return $response;
	}
	
	/**
	 * @FIXME
	 * @return array
	 */
	private function getThemeBlocksData()
	{
		$dummyEditable = new Editable\SelectVisual();
		$localeId = $this->getLocale()->getId();
		
		$dummyPropertyData = array(
			'id' => 'layout',
			'type' => $dummyEditable->getEditorType(),
			'inline' => $dummyEditable->isInlineEditable(),
			'label' => $dummyEditable->getLabel(),
			'value' => $dummyEditable->getDefaultValue($localeId),
			'group' => $dummyEditable->getGroupId(),
		)
			+ $dummyEditable->getAdditionalParameters();
		
		$dummyPropertyData['values'] = array(
			array(
				'id' => 'single',
				'title' => 'Single row',
				'icon' => '/components/FancyBlocks/NewsText/icons/columns-1.png',
			),
			array(
				'id' => 'three_one',
				'title' => 'Three + One',
				'icon' => '/components/FancyBlocks/NewsText/icons/columns-1.png',
			),
			array(
				'id' => 'one_three',
				'title' => 'One + Three',
				'icon' => '/components/FancyBlocks/NewsText/icons/columns-1.png',
			),
			array(
				'id' => 'two_two',
				'title' => 'Two + Two',
				'icon' => '/components/FancyBlocks/NewsText/icons/columns-1.png',
			),
			array(
				'id' => 'two_one_one',
				'title' => 'Two + One + One',
				'icon' => '/components/FancyBlocks/NewsText/icons/columns-1.png',
			),
			array(
				'id' => 'one_one_two',
				'title' => 'One + One + Two',
				'icon' => '/components/FancyBlocks/NewsText/icons/columns-1.png',
			),
			array(
				'id' => 'four',
				'title' => 'Four columns',
				'icon' => '/components/FancyBlocks/NewsText/icons/columns-1.png',
			),
		);
		
		$placeHolderContainerDummyBlock = array(
			'id' => 'list_one',
			'classname' => 'List',
			'hidden' => true,
			'group' => 'system',
			'property_groups' => array(),
			'title' => 'Layout',
			'description' => '',
			'icon' => '/cms/lib/supra/img/blocks/icons-items/default.png',
			'html' => null,

			'properties' => array(
				$dummyPropertyData,
			),
		);
		
		return $placeHolderContainerDummyBlock;
	}

}
