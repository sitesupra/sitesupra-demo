<?php

namespace Supra\Authorization;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Supra\Database\Entity; // for ::generateId() 

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

	protected function getDeleteAccessControlEntriesSql($oidPK)
	{
		return sprintf(
						'DELETE FROM %s WHERE object_identity_id = %s', $this->options['entry_table_name'], $this->connection->quote($oidPK)
		);
	}

	protected function getDeleteAccessControlEntrySql($acePK)
	{
		return sprintf(
						'DELETE FROM %s WHERE id = %s', $this->options['entry_table_name'], $this->connection->quote($acePK)
		);
	}

	/**
	 * Constructs the SQL for deleting an object identity.
	 *
	 * @param integer $pk
	 * @return string
	 */
	protected function getDeleteObjectIdentitySql($pk)
	{
		return sprintf(
						'DELETE FROM %s WHERE id = %s', $this->options['oid_table_name'], $this->connection->quote($pk)
		);
	}

	/**
	 * Constructs the SQL for deleting relation entries.
	 *
	 * @param integer $pk
	 * @return string
	 */
	protected function getDeleteObjectIdentityRelationsSql($pk)
	{
		return sprintf(
						'DELETE FROM %s WHERE object_identity_id = %s', $this->options['oid_ancestors_table_name'], $this->connection->quote($pk)
		);
	}

	/**
	 * Constructs the SQL for inserting an ACE.
	 *
	 * @param integer $classId
	 * @param integer|null $objectIdentityId
	 * @param string|null $field
	 * @param integer $aceOrder
	 * @param integer $securityIdentityId
	 * @param string $strategy
	 * @param integer $mask
	 * @param Boolean $granting
	 * @param Boolean $auditSuccess
	 * @param Boolean $auditFailure
	 * @return string
	 */
	protected function getInsertAccessControlEntrySql($classId, $objectIdentityId, $field, $aceOrder, $securityIdentityId, $strategy, $mask, $granting, $auditSuccess, $auditFailure)
	{
		$query = <<<QUERY
            INSERT INTO %s (
								id,
                class_id,
                object_identity_id,
                field_name,
                ace_order,
                security_identity_id,
                mask,
                granting,
                granting_strategy,
                audit_success,
                audit_failure
            )
            VALUES (%s, %s, %s, %s, %s, %s, %d, %s, %s, %s, %s)
QUERY;

		return sprintf(
						$query, $this->options['entry_table_name'], $this->connection->quote(Entity::generateId(__FUNCTION__)), $this->connection->quote($classId), null === $objectIdentityId ? 'NULL' : $this->connection->quote($objectIdentityId), null === $field ? 'NULL' : $this->connection->quote($field), $aceOrder, $this->connection->quote($securityIdentityId), $mask, $this->connection->getDatabasePlatform()->convertBooleans($granting), $this->connection->quote($strategy), $this->connection->getDatabasePlatform()->convertBooleans($auditSuccess), $this->connection->getDatabasePlatform()->convertBooleans($auditFailure)
		);
	}

	/**
	 * Constructs the SQL for inserting a new class type.
	 *
	 * @param string $classType
	 * @return string
	 */
	protected function getInsertClassSql($classType)
	{
		return sprintf(
						'INSERT INTO %s (id, class_type) VALUES (%s, %s)', $this->options['class_table_name'], $this->connection->quote(Entity::generateId(__FUNCTION__)), $this->connection->quote($classType)
		);
	}

	/**
	 * Constructs the SQL for inserting a relation entry.
	 *
	 * @param integer $objectIdentityId
	 * @param integer $ancestorId
	 * @return string
	 */
	protected function getInsertObjectIdentityRelationSql($objectIdentityId, $ancestorId)
	{
		return sprintf(
						'INSERT INTO %s (object_identity_id, ancestor_id) VALUES (%s, %s)', $this->options['oid_ancestors_table_name'], $this->connection->quote($objectIdentityId), $this->connection->quote($ancestorId)
		);
	}

	/**
	 * Constructs the SQL for inserting an object identity.
	 *
	 * @param string $identifier
	 * @param integer $classId
	 * @param Boolean $entriesInheriting
	 * @return string
	 */
	protected function getInsertObjectIdentitySql($identifier, $classId, $entriesInheriting)
	{
		$query = <<<QUERY
			INSERT INTO %s (id, class_id, object_identifier, entries_inheriting)
      VALUES (%s, %s, %s, %s)
QUERY;

		return sprintf(
						$query, $this->options['oid_table_name'], $this->connection->quote(Entity::generateId(__FUNCTION__)), $this->connection->quote($classId), $this->connection->quote($identifier), $this->connection->getDatabasePlatform()->convertBooleans($entriesInheriting)
		);
	}

	/**
	 * Constructs the SQL for inserting a security identity.
	 *
	 * @param SecurityIdentityInterface $sid
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	protected function getInsertSecurityIdentitySql(SecurityIdentityInterface $sid)
	{
		if ($sid instanceof UserSecurityIdentity) {

			$identifier = $sid->getClass() . '-' . $sid->getUsername();
			$username = true;
		}
		else if ($sid instanceof RoleSecurityIdentity) {

			$identifier = $sid->getRole();
			$username = false;
		}
		else {
			throw new \InvalidArgumentException('$sid must either be an instance of UserSecurityIdentity, or RoleSecurityIdentity.');
		}

		return sprintf(
						'INSERT INTO %s (id, identifier, username) VALUES (%s, %s, %s)', $this->options['sid_table_name'], $this->connection->quote(Entity::generateId(__FUNCTION__)), $this->connection->quote($identifier), $this->connection->getDatabasePlatform()->convertBooleans($username)
		);
	}

	/**
	 * Constructs the SQL for selecting an ACE.
	 *
	 * @param integer $classId
	 * @param integer $oid
	 * @param string $field
	 * @param integer $order
	 * @return string
	 */
	protected function getSelectAccessControlEntryIdSql($classId, $oid, $field, $order)
	{
		return sprintf(
						'SELECT id FROM %s WHERE class_id = %s AND %s AND %s AND ace_order = %d', $this->options['entry_table_name'], $this->connection->quote($classId), null === $oid ? $this->connection->getDatabasePlatform()->getIsNullExpression('object_identity_id') : 'object_identity_id = ' . $this->connection->quote($oid), null === $field ? $this->connection->getDatabasePlatform()->getIsNullExpression('field_name') : 'field_name = ' . $this->connection->quote($field), $order
		);
	}

	/**
	 * Constructs the SQL for selecting the primary key associated with
	 * the passed class type.
	 *
	 * @param string $classType
	 * @return string
	 */
	protected function getSelectClassIdSql($classType)
	{
		return sprintf(
						'SELECT id FROM %s WHERE class_type = %s', $this->options['class_table_name'], $this->connection->quote($classType)
		);
	}

	/**
	 * Constructs the SQL for selecting the primary key of a security identity.
	 *
	 * @param SecurityIdentityInterface $sid
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	protected function getSelectSecurityIdentityIdSql(SecurityIdentityInterface $sid)
	{
		if ($sid instanceof UserSecurityIdentity) {

			$identifier = $sid->getClass() . '-' . $sid->getUsername();
			$username = true;
		}
		else if ($sid instanceof RoleSecurityIdentity) {

			$identifier = $sid->getRole();
			$username = false;
		}
		else {
			throw new \InvalidArgumentException('$sid must either be an instance of UserSecurityIdentity, or RoleSecurityIdentity.');
		}

		return sprintf(
						'SELECT id FROM %s WHERE identifier = %s AND username = %s', $this->options['sid_table_name'], $this->connection->quote($identifier), $this->connection->getDatabasePlatform()->convertBooleans($username)
		);
	}

	/**
	 * Constructs the SQL for updating an object identity.
	 *
	 * @param integer $pk
	 * @param array $changes
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	protected function getUpdateObjectIdentitySql($pk, array $changes)
	{
		if (0 === count($changes)) {
			throw new \InvalidArgumentException('There are no changes.');
		}

		return sprintf(
						'UPDATE %s SET %s WHERE id = %s', $this->options['oid_table_name'], implode(', ', $changes), $this->connection->quote($pk)
		);
	}

	/**
	 * Constructs the SQL for updating an ACE.
	 *
	 * @param integer $pk
	 * @param array $sets
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	protected function getUpdateAccessControlEntrySql($pk, array $sets)
	{
		if (0 === count($sets)) {
			throw new \InvalidArgumentException('There are no changes.');
		}

		return sprintf(
						'UPDATE %s SET %s WHERE id = %s', $this->options['entry_table_name'], implode(', ', $sets), $this->connection->quote($pk)
		);
	}

	protected function getLookupSql($ids)
	{
		foreach ($ids as &$id) {
			$id = $this->connection->quote($id);
		}

		$sql = parent::getLookupSql($ids);

		return $sql;
	}

	public function removeSidAces(UserSecurityIdentity $sid)
	{
		$identifier = $sid->getClass() . '-' . $sid->getUsername();

		$identifier = str_replace('\\', '\\\\', $identifier);

		$sql = <<<DELETECLAUSE
			DELETE ae 
			FROM acl_entries ae 
				LEFT JOIN acl_security_identities asi ON ae.security_identity_id = asi.id 
			WHERE asi.identifier = '{$identifier}';
DELETECLAUSE;

		$this->connection->executeQuery($sql);
	}

}
