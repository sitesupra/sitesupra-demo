<?php

namespace Supra\Form\Constraints;

use Supra\Form\Constraints\Abstraction\Constraint;

/**
 * @Annotation
 *
 * @api
 */
class File extends Constraint
{
    public $maxSize = null;
    public $mimeTypes = array();
    public $notFoundMessage = 'The file could not be found.';
    public $notReadableMessage = 'The file is not readable.';
    public $maxSizeMessage = 'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}.';
    public $mimeTypesMessage = 'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.';

    public $uploadIniSizeErrorMessage   = 'The file is too large. Allowed maximum size is {{ limit }} {{ suffix }}.';
    public $uploadFormSizeErrorMessage  = 'The file is too large.';
    public $uploadPartialErrorMessage   = 'The file was only partially uploaded.';
    public $uploadNoFileErrorMessage    = 'No file was uploaded.';
    public $uploadNoTmpDirErrorMessage  = 'No temporary folder was configured in php.ini.';
    public $uploadCantWriteErrorMessage = 'Cannot write temporary file to disk.';
    public $uploadExtensionErrorMessage = 'A PHP extension caused the upload to fail.';
    public $uploadErrorMessage          = 'The file could not be uploaded.';
}
