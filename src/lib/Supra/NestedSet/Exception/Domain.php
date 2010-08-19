<?php

namespace Supra\NestedSet\Exception;

use DomainException;

/**
 * Error on argument not inside the domain
 */
class Domain extends DomainException implements INestedSetException
{}