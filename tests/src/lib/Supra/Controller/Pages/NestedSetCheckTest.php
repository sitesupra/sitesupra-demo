<?php

namespace Supra\Tests\Controller\Pages;

/**
 * Description of NestedSetCheckTest
 */
class NestedSetCheckTest extends \PHPUnit_Framework_TestCase
{
	public function testStructure()
	{
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager('');
		$conn = $em->getConnection();
		
		// Left index always less than right
		$st = $conn->prepare("select true from su_AbstractPage where lft >= rgt");
		$st->execute();
		$value = $st->fetch(\PDO::FETCH_COLUMN);
		self::assertEmpty($value);
		
		// Index is unique, starts with 1, no spaces
		$st = $conn->prepare("select 
				-- No duplicates
				(count(distinct ind) = count(ind)) 
				-- starts with 1
				AND (MIN(ind) = 1) 
				-- ends with count
				AND (MAX(ind) = COUNT(ind))
			FROM (select lft as ind from su_AbstractPage where discr IN ('page', 'application', 'group')
			UNION ALL 
			select rgt as ind from su_AbstractPage where discr IN ('page', 'application', 'group')) AS x");
		$st->execute();
		$value = $st->fetch(\PDO::FETCH_COLUMN);
		self::assertTrue(is_null($value) || $value = 1);
		
		// Index is unique, starts with 1, no spaces (for templates)
		$st = $conn->prepare("select 
				-- No duplicates
				(count(distinct ind) = count(ind)) 
				-- starts with 1
				AND (MIN(ind) = 1) 
				-- ends with count
				AND (MAX(ind) = COUNT(ind))
			FROM (select lft as ind from su_AbstractPage where discr IN ('template')
			UNION ALL 
			select rgt as ind from su_AbstractPage where discr IN ('template')) AS x");
		$st->execute();
		$value = $st->fetch(\PDO::FETCH_COLUMN);
		self::assertTrue(is_null($value) || $value = 1);
	}
}
