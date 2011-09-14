<?php

namespace Supra\Cms\ContentManager\Blocks;

use Supra\Cms\ContentManager\PageManagerAction;
use Supra\Controller\Pages\BlockControllerCollection;
use Supra\Request;
use Supra\Response;

class BlocksAction extends PageManagerAction
{

	public function blocksAction()
	{
		$bc = BlockControllerCollection::getInstance();
		$configurationList = $bc->getConfigurationList();
		
		$response = array();
		
		foreach ($configurationList as $conf) {
			
			$obj = new $conf->controllerClass;
			$propertyDefinition = $obj->getPropertyDefinition();
			
			$properties = null;
			
			foreach ($propertyDefinition as $key => $property) {
				$properties[] = array(
					'id' => $key,
					'type' => $property->getEditorType(),
					'inline' => $property->isInlineEditable(),
					'label' => $property->getLabel(),
					'value' => $property->getDefaultValue(),
				);
			}
			
			
			$response[] = array(
				'id' => $conf->id,
				'title' => $conf->title,
				'description' => $conf->description,
				'icon' => $conf->getIconWebPath(),
				'classname' => $conf->classname,
				'properties' => $properties,
			);
					
			
		}
		
		$this->getResponse()->setResponseData($response);
	}

}