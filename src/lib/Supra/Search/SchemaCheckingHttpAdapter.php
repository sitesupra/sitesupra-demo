<?php

namespace Supra\Search;

use \Solarium_Client_Adapter_Http;

class SchemaCheckingHttpAdapter extends Solarium_Client_Adapter_Http
{

	public function execute($request)
	{
		$options = $this->getOptions();
		
		$schemaUrl = 'http://' . $options['host'] . ':' . $options['port'] . $options['path'] . 
				'/admin/file/?contentType=text/xml;charset=utf-8&file=schema.xml';
		
		$solrSchemaMd5 = md5_file($schemaUrl);
		
		$localSchemaMd5 = md5_file(SUPRA_CONF_PATH . '/solr/schema.xml');
		
		if( $solrSchemaMd5 != $localSchemaMd5 )
		{
			throw new Exception\RuntimeException('Local schema.xml and one used in Solr do not match.');
		}

		return parent::execute($request);
	}

}

