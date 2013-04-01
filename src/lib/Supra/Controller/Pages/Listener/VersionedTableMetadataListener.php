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
		'Supra\Controller\Pages\Entity\Abstraction\Localization',
		'Supra\Controller\Pages\Entity\PageLocalization',
		'Supra\Controller\Pages\Entity\TemplateLocalization',
		
		'Supra\Controller\Pages\Entity\Abstraction\PlaceHolder',
		'Supra\Controller\Pages\Entity\PagePlaceHolder',
		'Supra\Controller\Pages\Entity\TemplatePlaceHolder',
		'Supra\Controller\Pages\Entity\TemplatePlaceHolderGroup',
		
		'Supra\Controller\Pages\Entity\Abstraction\Block',
		'Supra\Controller\Pages\Entity\PageBlock',
		'Supra\Controller\Pages\Entity\TemplateBlock',
		
		'Supra\Controller\Pages\Entity\BlockProperty',
		'Supra\Controller\Pages\Entity\BlockPropertyMetadata',
		
		'Supra\Controller\Pages\Entity\ReferencedElement\ReferencedElementAbstract',
		'Supra\Controller\Pages\Entity\ReferencedElement\LinkReferencedElement',
		'Supra\Controller\Pages\Entity\ReferencedElement\ImageReferencedElement',
		'Supra\Controller\Pages\Entity\ReferencedElement\VideoReferencedElement',
		'Supra\Controller\Pages\Entity\ReferencedElement\IconReferencedElement',
	);
}
