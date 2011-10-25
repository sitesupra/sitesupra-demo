<?php

namespace Supra\Controller\Pages\Annotation;

use Doctrine\Common\Annotations\Annotation;

/** @Annotation */
class SkipForeignKey extends Annotation {
	public $type;
}
/** @Annotation */
class SkipForeignKeyOnCreate extends Annotation {
	public $type;
} 
/** @Annotation */
class SkipUniqueConstraints {} 
/** @Annotation */
class InheritOnCreate {} 

/** @Annotation */
class Id {}
/** @Annotation */
class Column extends Annotation {
    public $type = 'string';
    public $length;
    public $precision = 0;
    public $scale = 0;
    public $unique = false;
    public $nullable = false;
    public $name;
    public $options = array();
    public $columnDefinition;
}
/** @Annotation */
class OneToOne extends Annotation {
    public $targetEntity;
    public $mappedBy;
    public $inversedBy;
    public $cascade;
    public $fetch = 'LAZY';
    public $orphanRemoval = false;
}