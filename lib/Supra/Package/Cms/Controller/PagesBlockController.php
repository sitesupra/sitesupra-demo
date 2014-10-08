<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Pages\Block\BlockConfiguration;

class PagesBlockController extends AbstractPagesController
{
	public function blocksListAction()
	{
		$blockCollection = $this->getBlockCollection();

		$blockData = $groupData
				= array();

		foreach ($blockCollection->getGroupConfigurations() as $configuration) {
			$groupData[] = array(
				'id'		=> $configuration->getName(),
				'title'		=> $configuration->getTitle(),
				'default'	=> $configuration->isDefault(),
			);
		}

		$defaultGroup = $blockCollection->findDefaultGroupConfiguration();

		foreach ($blockCollection->getConfigurations() as $configuration) {

			$groupName = $configuration->getGroupName();

			if ($groupName === null) {
				if ($defaultGroup === null) {
					throw new \RuntimeException(sprintf(
							'Block [%s] has no group name set and there is no default group in collection.'
					));
				}

				$groupName = $defaultGroup->getName();
			}

			$blockData[] = array(
				'id'			=> $configuration->getControllerClassId(),
				'title'			=> $configuration->getTitle(),
				'description'	=> $configuration->getDescription(),
				'icon'			=> $configuration->getIcon(),
				'tooltip'		=> $configuration->getTooltip(),
				'group'			=> $groupName,
				'insertable'	=> $configuration->isInsertable(),
				'icon'			=> $this->resolveWebPath($configuration->getIcon()),
				'properties'	=> $this->getBlockPropertyConfigurationData($configuration),
				'property_groups'			=> array(), // @TODO
				'preferred_property_group'	=> array(), // @TODO
				// @TODO: sub-array with options for frontend?
				'classname'		=> $configuration->getCmsClassName(),
			);
		}

		return new SupraJsonResponse(array(
			'groups' => $groupData,
			'blocks' => $blockData,
		));
	}

	/**
	 * @param BlockConfiguration $blockConfiguration
	 * @return array
	 */
	private function getBlockPropertyConfigurationData(BlockConfiguration $blockConfiguration)
	{
		$propertyData = array();

		$localeId = $this->getCurrentLocale()
				->getId();

		foreach ($blockConfiguration->getProperties() as $configuration) {

			$editable = $configuration->getEditable();

			$propertyData[] = array(
				'id'	=> $configuration->getName(),
				'value'	=> $configuration->getDefaultValue($localeId),

				// Editable information
				'type'			=> $editable->getEditorType(),
				'label'			=> $editable->getLabel(),
				'description'	=> $editable->getDescription(),
				// @TODO: check if this feature from portal. remove if it is.
				'group'			=> $editable->getGroupId(),
				
			) + $editable->getAdditionalParameters();
		}

		return $propertyData;
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

//			$controller = $blockCollection->createBlockController($conf->class);
			$propertyDefinition = $conf->properties;

			$properties = $this->gatherPropertyArray($propertyDefinition);

			$response['blocks'][] = array(
				'id' => $conf->id,
				'group' => $blockGroup,
				'property_groups' => $conf->propertyGroups,
				'title' => $conf->title,
				'description' => $conf->description,
				'icon' => $conf->iconWebPath,
				'preferredPropertyGroup' => $conf->preferredPropertyGroup,
				'classname' => $conf->cmsClassname,
				'properties' => $properties,
				'hidden' => $conf->hidden,
				'html' => $conf->html,
				'tooltip' => $conf->tooltip,
			);

			$titles[] = $conf->title;
		}

		// Order by block title
		array_multisort($titles, $response['blocks']);

		$themeBlockData = $this->getThemePlaceholderLayoutsBlockData();
		if ( ! empty($themeBlockData)) {
			array_push($response['blocks'], $themeBlockData);
		}

		$this->getResponse()->setResponseData($response);
	}
}
