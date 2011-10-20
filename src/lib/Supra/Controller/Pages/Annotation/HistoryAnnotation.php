<?php

namespace Supra\Controller\Pages\Annotation;

use Doctrine\Common\Annotations\Annotation;

/** @Annotation */
class Id {}
/** @Annotation */
class SkipForeignKey extends Annotation {
	public $type;
} 
class SkipForeignKeyOnCreate extends Annotation {
	public $type;
} 
/** @Annotation */
class SkipPrefix {} 
/** @Annotation */
class InheritOnCreate {} 
/** @Annotation */
class ManyToOne extends Annotation {
    public $targetEntity;
    public $cascade;
    public $fetch = 'LAZY';
    public $inversedBy;
}
/** @Annotation */
class JoinColumn extends Annotation {
    public $name;
    public $fieldName;
    public $referencedColumnName = 'id';
    public $unique = false;
    public $nullable = true;
    public $onDelete;
    public $onUpdate;
    public $columnDefinition;
}
/** @Annotation */
final class Column extends Annotation {
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