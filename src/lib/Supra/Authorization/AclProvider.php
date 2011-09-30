<?php


namespace Supra\Authorization;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;

/**
 * This extension just adds some extra methods.
  */
class AclProvider extends MutableAclProvider
{
	/**
	 * Returns ObjectIdentity'ies for selected class currently present in db. This is quite ugly.
	 * @param type $class 
	 */
	public function getOidsByClass($class) 
	{
		$ref = new \ReflectionClass($class);
		
		$class = str_replace("\\", "\\\\", $ref->getName());
		
		$sql = <<<SELECTCLAUSE
			SELECT 
				aoi.object_identifier, ac.class_type
			FROM
				{$this->options['oid_table_name']} aoi
				LEFT JOIN {$this->options['class_table_name']} ac ON aoi.class_id = ac.id
			WHERE
				ac.class_type = '{$class}'
SELECTCLAUSE;
		
		$oids = array();
		foreach ($this->connection->executeQuery($sql)->fetchAll() as $data) {
			$oids[] = new ObjectIdentity($data['object_identifier'], $data['class_type']);
		}

		return $oids;
	}
}
