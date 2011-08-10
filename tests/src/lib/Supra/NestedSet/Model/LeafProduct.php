<?php

namespace Supra\Tests\NestedSet\Model;

use Supra\NestedSet\Node\NodeLeafInterface;

/**
 * @Entity(repositoryClass="Supra\Tests\NestedSet\Model\ProductRepository")
 */
class LeafProduct extends Product implements NodeLeafInterface
{
	
}
