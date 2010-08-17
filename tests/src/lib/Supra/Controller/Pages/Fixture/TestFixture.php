<?php

namespace Supra\Tests\Controller\Pages\Fixture;

use Supra\Controller\Pages\Entity,
		Supra\Database\Doctrine;

class TestFixture extends Fixture
{
	const CONNECTION_NAME = 'test';

	/**
	 */
	public function testFixture()
	{
		return parent::testFixture();
	}
	
}