<?php

namespace Supra\FileStorage\Entity;

/**
 * File object
 * @Entity
 * @Table(name="file")
 */
class File extends Abstraction\File
{

	/**
	 * @Column(type="string", name="mime_type", nullable=false)
	 * @var string
	 */
	protected $mimeType;

	/**
	 * @Column(type="integer", name="file_size", nullable=false)
	 * @var integer
	 */
	protected $fileSize;

	/**
	 * @OneToMany(targetEntity="MetaData", mappedBy="master", cascade={"persist", "remove"}, indexBy="locale")
	 * @var Collection
	 */
	protected $metaData;

}
