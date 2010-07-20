<?php

namespace Supra\Controller\Pages\Entity;

/**
 * @Entity
 * @Table(name="page_block")
 */
class PageBlock extends Abstraction\Block
{
	/**
	 * @ManyToOne(targetEntity="PagePlaceHolder", inversedBy="blocks")
	 * @var PagePlaceHolder
	 */
	protected $placeHolder;
}