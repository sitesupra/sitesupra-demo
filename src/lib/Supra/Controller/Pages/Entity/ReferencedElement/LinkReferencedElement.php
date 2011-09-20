<?php

namespace Supra\Controller\Pages\Entity\ReferencedElement;

use Supra\Controller\Pages\Entity\PageData;
use Supra\FileStorage\Entity\File;

/**
 * @Entity
 */
class LinkReferencedElement extends ReferencedElementAbstract
{
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $resource;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $href;
	
	/**
	 * @Column(type="string")
	 * @var string
	 */
	protected $title;
	
	/**
	 * @ManyToOne(targetEntity="Supra\Controller\Pages\Entity\PageData")
	 * @var PageData
	 */
	protected $page;
	
	/**
	 * @ManyToOne(targetEntity="Supra\FileStorage\Entity\File")
	 * @var File
	 */
	protected $file;
}
