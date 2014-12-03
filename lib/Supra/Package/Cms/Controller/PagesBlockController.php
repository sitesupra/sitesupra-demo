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
				'id'			=> $configuration->getName(),
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

		foreach ($blockConfiguration->getProperties() as $configuration) {

			$editable = $configuration->getEditable();

			$propertyData[] = array(
				'id'	=> $configuration->getName(),
				// Editable information
				'value'			=> $editable->getDefaultValue(),
				'type'			=> $editable->getEditorType(),
				'label'			=> $editable->getLabel(),
				'description'	=> $editable->getDescription(),

// @FIXME: block property configuration option
//				// @TODO: check if this feature from portal. remove if it is.
//				'group'			=> $editable->getGroupId(),
				
			) + $editable->getAdditionalParameters();
		}

		return $propertyData;
	}
}
