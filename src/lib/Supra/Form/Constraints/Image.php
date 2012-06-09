<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class Image extends File
{
    public $mimeTypes = 'image/*';
    public $minWidth = null;
    public $maxWidth = null;
    public $maxHeight = null;
    public $minHeight = null;

    public $mimeTypesMessage = 'This file is not a valid image.';
    public $sizeNotDetectedMessage = 'The size of the image could not be detected.';
    public $maxWidthMessage = 'The image width is too big ({{ width }}px). Allowed maximum width is {{ max_width }}px.';
    public $minWidthMessage = 'The image width is too small ({{ width }}px). Minimum width expected is {{ min_width }}px.';
    public $maxHeightMessage = 'The image height is too big ({{ height }}px). Allowed maximum height is {{ max_height }}px.';
    public $minHeightMessage = 'The image height is too small ({{ height }}px). Minimum height expected is {{ min_height }}px.';
}
