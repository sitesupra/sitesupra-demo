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
		'Supra\Package\Cms\Entity\Abstraction\Localization',
		'Supra\Package\Cms\Entity\PageLocalization',
		'Supra\Package\Cms\Entity\TemplateLocalization',
		
		'Supra\Package\Cms\Entity\Abstraction\PlaceHolder',
		'Supra\Package\Cms\Entity\PagePlaceHolder',
		'Supra\Package\Cms\Entity\TemplatePlaceHolder',
		'Supra\Package\Cms\Entity\PlaceHolderGroup',
		
		'Supra\Package\Cms\Entity\Abstraction\Block',
		'Supra\Package\Cms\Entity\PageBlock',
		'Supra\Package\Cms\Entity\TemplateBlock',
		
		'Supra\Package\Cms\Entity\BlockProperty',
		'Supra\Package\Cms\Entity\BlockPropertyMetadata',
		
		'Supra\Package\Cms\Entity\ReferencedElement\ReferencedElementAbstract',
		'Supra\Package\Cms\Entity\ReferencedElement\LinkReferencedElement',
		'Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement',
		'Supra\Package\Cms\Entity\ReferencedElement\VideoReferencedElement',
		'Supra\Package\Cms\Entity\ReferencedElement\IconReferencedElement',
	);
}
