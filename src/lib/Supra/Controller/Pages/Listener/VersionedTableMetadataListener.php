<?php

namespace Supra\Controller\Pages\Listener;

/**
 * Abstract class for versioned table listeners
 */
abstract class VersionedTableMetadataListener
{
	/**
	 * Entities to be versioned
	 * @var array
	 */
	protected static $versionedEntities = array(
		'Supra\Controller\Pages\Entity\Abstraction\Data',
		'Supra\Controller\Pages\Entity\PageData',
		'Supra\Controller\Pages\Entity\TemplateData',
		
		'Supra\Controller\Pages\Entity\Abstraction\PlaceHolder',
		'Supra\Controller\Pages\Entity\PagePlaceHolder',
		'Supra\Controller\Pages\Entity\TemplatePlaceHolder',
		
		'Supra\Controller\Pages\Entity\Abstraction\Block',
		'Supra\Controller\Pages\Entity\PageBlock',
		'Supra\Controller\Pages\Entity\TemplateBlock',
		
		'Supra\Controller\Pages\Entity\BlockProperty',
		'Supra\Controller\Pages\Entity\BlockPropertyMetadata',
		
		'Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract',
		'Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement',
		'Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement',
	);
}
