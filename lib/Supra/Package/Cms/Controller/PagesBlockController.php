<?php

namespace Supra\Package\Cms\Controller;

use Supra\Core\HttpFoundation\SupraJsonResponse;
use Supra\Package\Cms\Pages\Block\Config;

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

			$propertyConfigData = array();

			foreach ($configuration->getProperties() as $propertyConfig) {
				$propertyConfigData[] = $this->getPropertyData($propertyConfig);
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
				'properties'	=> $propertyConfigData,
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
	 * @param Config\AbstractPropertyConfig $property
	 * @return array
	 */
	private function getPropertyData(Config\AbstractPropertyConfig $property)
	{
		if ($property instanceof Config\PropertySetConfig) {

			$setData = array();

			foreach ($property as $subProperty) {
				$setData[] = $this->getPropertyData($subProperty);
			}

			return array(
				'id'		=> $property->name,
				'type'		=> 'Set',
				'properties'	=> $setData,
			);

		} elseif ($property instanceof Config\PropertyListConfig) {

			return array(
				'id'			=> $property->name,
				'type'			=> 'Collection',
				'properties'	=> $this->getPropertyData($property->getListItem()),
			);

		} elseif ($property instanceof Config\PropertyConfig) {

			$editable = $property->getEditable();

			return array(
				'id'			=> $property->name,
				'value'			=> $editable->getDefaultValue(),
				'type'			=> $editable->getEditorType(),
				'label'			=> $editable->getLabel(),
				'description'	=> $editable->getDescription(),

			) + $editable->getAdditionalParameters();
		} else {
			
			throw new \UnexpectedValueException(sprintf('Don\'t know what do to with [%s].', get_class($property)));
		}
	}
}
