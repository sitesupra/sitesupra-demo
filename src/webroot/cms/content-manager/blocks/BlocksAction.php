<?php

namespace Supra\Cms\ContentManager\Blocks;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Request;
use Supra\Response;
use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;

class BlocksAction extends PageManagerAction
{
	/**
	 * Collects block definition information
	 */
	public function blocksAction()
	{
		$blockCollection = BlockControllerCollection::getInstance();
		$configurationList = $blockCollection->getConfigurationList();
		
		$response = array();
		
		/* @var $conf BlockControllerConfiguration */
		foreach ($configurationList as $blockId => $conf) {
			
			$controller = $blockCollection->getBlockController($blockId);
			$propertyDefinition = (array) $controller->getPropertyDefinition();
			
			$properties = array();
			
			foreach ($propertyDefinition as $key => $property) {
				/* @var $property \Supra\Editable\EditableInterface */
				$properties[] = array(
					'id' => $key,
					'type' => $property->getEditorType(),
					'inline' => $property->isInlineEditable(),
					'label' => $property->getLabel(),
					'value' => $property->getDefaultValue(),
					'group' => $property->getGroupLabel(),
				) + $property->getAdditionalParameters();
			}
			
			$response[] = array(
				'id' => $conf->id,
				//TODO: hardcoded
				'group' => "Site features",
				'title' => $conf->title,
				'description' => $conf->description,
				'icon' => $conf->iconWebPath,
				'classname' => $conf->cmsClassname,
				'properties' => $properties,
			);
		}
		
		$this->getResponse()->setResponseData($response);
	}

}