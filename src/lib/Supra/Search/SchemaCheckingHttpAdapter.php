<?php

namespace Supra\Search;

use Solarium_Client_Adapter_Http;

/**
 * Before the request checks if local and solr schemas are equal by comparing their hashsums
 */
class SchemaCheckingHttpAdapter extends Solarium_Client_Adapter_Http
{
	/**
	 * {@inheritdoc}
	 */
	public function execute($request)
	{
		$options = $this->getOptions();

		$schemaUrl = 'http://' . $options['host'] . ':' . $options['port'] . $options['path'] .
				'/admin/file/?contentType=text/xml;charset=utf-8&file=schema.xml';

		$solrSchemaMd5 = md5_file($schemaUrl);

		if ($solrSchemaMd5 === false) {
			throw new Exception\BadSchemaException('Failed to fetch schema from Solr. URL: ' . $schemaUrl);
		}

		$localSchemaFilename = SUPRA_CONF_PATH . '/solr/schema.xml';

		$localSchemaMd5 = md5_file($localSchemaFilename);

		if ($localSchemaMd5 === false) {
			throw new Exception\BadSchemaException('Failed to fetch local Solr schema. Path: ' . $localSchemaFilename);
		}

		if ($solrSchemaMd5 != $localSchemaMd5) {
			throw new Exception\BadSchemaException('Local schema.xml and one used in Solr do not match.');
		}

		return parent::execute($request);
	}

}

