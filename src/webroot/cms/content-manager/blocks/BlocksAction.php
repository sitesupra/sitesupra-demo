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
		
        $dummyEditable->setIconStyle('html');
        $dummyEditable->setStyle('mid');
        $dummyEditable->setCss('
            .su-button .su-button-bg {
                padding: 0;
            }
            .su-button .su-button-bg div {
                height: 57px;
            }
            table {
                width: 100%;
                border-spacing: 3px;
                border-collapse: separate;
            }
            td { 
                background: url(/components/FancyBlocks/Text/icons/background.png) 0 0 repeat-x;
                vertical-align: middle; text-align: center;
                text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.6), 0 1px 0 rgba(255, 255, 255, 0.5);
                color: #054873;
                font-size: 18px;
                font-weight: bold;
            }
            td.h-1 {
                height: 24px;
                background-position: 0 -52px;
            }
            tr + tr td.h-1 {
                height: 24px;
                background-position: 0 -77px;
            }
            td.h-2 {
                height: 52px;
                font-size: 30px;
            }
            td.w-12 { width: 100%; }
            td.w-9  { width: 75%; }
            td.w-6  { width: 50%; }
            td.w-4  { width: 33.3%; }
            td.w-3  { width: 25%; }
        ');
        
		$dummyPropertyData = array(
			'id' => 'layout',
			'type' => $dummyEditable->getEditorType(),
			'inline' => $dummyEditable->isInlineEditable(),
			'label' => $dummyEditable->getLabel(),
			'value' => $dummyEditable->getDefaultValue($localeId),
			'group' => $dummyEditable->getGroupId(),
			'style' => 'mid',
            'iconStyle' => 'html',
		)
			+ $dummyEditable->getAdditionalParameters();
		
		$dummyPropertyData['values'] = array(
			array(
				'id' => 'single',
				'title' => 'Single row',
				'html' => '<table><tr><td class="h-2 w-12">4</td></tr></table>',
			),
			array(
				'id' => 'three_one',
				'title' => 'Three + One',
				'html' => '<table><tr><td class="h-2 w-9">3</td><td class="h-2 w-3">1</td></tr></table>',
			),
			array(
				'id' => 'one_three',
				'title' => 'One + Three',
				'html' => '<table><tr><td class="h-2 w-3">1</td><td class="h-2 w-9">3</td></tr></table>',
			),
			array(
				'id' => 'two_two',
				'title' => 'Two + Two',
				'html' => '<table><tr><td class="h-2 w-6">2</td><td class="h-2 w-2">2</td></tr></table>',
			),
			array(
				'id' => 'two_one_one',
				'title' => 'Two + One + One',
				'html' => '<table><tr><td class="h-2 w-6">2</td><td class="h-2 w-3">1</td><td class="h-2 w-3">1</td></tr></table>',
			),
			array(
				'id' => 'one_one_two',
				'title' => 'One + One + Two',
				'html' => '<table><tr><td class="h-2 w-3">1</td><td class="h-2 w-3">1</td><td class="h-2 w-6">2</td></tr></table>',
			),
			array(
				'id' => 'four',
				'title' => 'Four columns',
				'html' => '<table><tr><td class="h-2 w-3">1</td><td class="h-2 w-3">1</td><td class="h-2 w-3">1</td><td class="h-2 w-3">1</td></tr></table>',
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
