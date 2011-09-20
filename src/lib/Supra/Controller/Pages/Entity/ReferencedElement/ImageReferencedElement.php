<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\FileStorage\Entity\Image;

/**
 * @Entity
 */
class ImageReferencedElement extends ReferencedElementAbstract
{
	/**
	 * @ManyToOne(targetEntity="Supra\FileStorage\Entity\Image")
	 * @var Image
	 */
	protected $image;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $align;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $style;
	
	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $width;
	
	/**
	 * @Column(type="integer")
	 * @var integer
	 */
	protected $height;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $alternativeText;
}
