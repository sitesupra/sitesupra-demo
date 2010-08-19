<?php

namespace Supra\NestedSet\Exception;

use RuntimeException;

/**
 * Exception for not allowed operations
 */
class InvalidOperation extends RuntimeException implements INestedSetException
{}