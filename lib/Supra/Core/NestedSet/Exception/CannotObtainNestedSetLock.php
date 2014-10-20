<?php

namespace Supra\Core\NestedSet\Exception;

/**
 * Raised when nested set lock cannot be obtained, usually because of queue
 */
class CannotObtainNestedSetLock extends \RuntimeException implements NestedSetException
{}
