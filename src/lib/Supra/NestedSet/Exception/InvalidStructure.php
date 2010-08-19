<?php

namespace Supra\NestedSet\Exception;

use LogicException;

/**
 * Errors of invalid tree structure
 */
class InvalidStructure extends LogicException implements INestedSetException
{}