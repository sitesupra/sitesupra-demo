 - VersionedEntitySchemaListener:
	modifies entity class metadata by adding _draft prefix to table names.
	If the entity XYZ implements VersionedEntityInterface, it will be loaded from "su_XYZ_draft" table.

	This listener must be attached only to Draft EntityManager.

- VersionedEntityRevisionSetterListener:
	watches for changes, updates the revision.

	This listener must be attached only to Draft EntityManager.
