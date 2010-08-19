<?php

namespace Supra\NestedSet\Exception;

use BadMethodCallException;

/**
 * Error on undefined method call
 */
class BadMethodCall extends BadMethodCallException implements INestedSetException
{}