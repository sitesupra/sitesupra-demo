<?php

namespace Supra\Tests\FileStorage;

/**
 * Description of NestedSetCheckTest
 */
class NestedSetCheckTest extends \PHPUnit_Framework_TestCase
{
	public function testPublicStructure()
	{
		$this->testStructure('');
	}
	
	public function testTestStructure()
	{
		$this->testStructure($this);
	}
	
	private function testStructure($scope)
	{
		$em = \Supra\ObjectRepository\ObjectRepository::getEntityManager($scope);
		$conn = $em->getConnection();
		
		// Left index always less than right
		$st = $conn->prepare("select true from su_file_abstraction where lft >= rgt");
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
			FROM (select lft as ind from su_file_abstraction
			UNION ALL 
			select rgt as ind from su_file_abstraction) AS x");
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
			FROM (select lft as ind from su_file_abstraction
			UNION ALL 
			select rgt as ind from su_file_abstraction) AS x");
		$st->execute();
		$value = $st->fetch(\PDO::FETCH_COLUMN);
		self::assertTrue(is_null($value) || $value = 1);
	}
}
