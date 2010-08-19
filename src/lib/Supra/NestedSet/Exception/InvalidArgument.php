<?php

namespace Supra\NestedSet\Exception;

use InvalidArgumentException;

/**
 * Error on invalid argument received
 */
class InvalidArgument extends InvalidArgumentException implements INestedSetException
{}