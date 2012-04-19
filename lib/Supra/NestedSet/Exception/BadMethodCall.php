<?php

namespace Supra\NestedSet\Exception;

/**
 * Error on undefined method call
 */
class BadMethodCall extends \BadMethodCallException implements NestedSetException
{}