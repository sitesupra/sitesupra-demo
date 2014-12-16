<?php

namespace Sample\Blocks;

use Supra\Package\Cms\Pages\Block\BlockConfiguration;
use Supra\Package\Cms\Pages\Block\Mapper\AttributeMapper;
use Supra\Package\Cms\Pages\Block\Mapper\PropertyMapper;

use Supra\Package\Cms\Pages\Block\BlockPropertyConfiguration;
use Supra\Package\Cms\Editable\Editable;

class CollectionGallery extends BlockConfiguration
{
	protected function configureAttributes(AttributeMapper $mapper)
	{
		$mapper->title('Collection Gallery (TEST)')
				->icon('sample:blocks/gallery.png')
				->cmsClassName('Gallery')
				->template('sample:blocks/collection-gallery.html.twig')
				->controller('\\Sample\\Blocks\\TestController')
				;
	}

	protected function configureProperties(PropertyMapper $mapper)
	{
		$editable = Editable::getEditable('collection');
		/* @var $editable \Supra\Package\Cms\Editable\Collection */

		$imageEditable = Editable::getEditable('image');

		$editable->setCollectionEditable($imageEditable);

		$this->addProperty(new BlockPropertyConfiguration('images', $editable));
	}
}