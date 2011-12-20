<?php

namespace Supra\Search;

use Supra\Controller\Pages\Configuration\BlockControllerConfiguration;

class SearchControllerConfiguration extends BlockControllerConfiguration
{
	/**
	 * @var string
	 */
	public $formTemplateFilename;
	
	/**
	 * @var string
	 */
	public $resultsTemplateFilename;

	public function configure()
	{
		$this->controllerClass = SearchController::CN();
		$this->title = 'Search';
		$this->description = 'Search controller';
		$this->cmsClassname = 'Editable';
		
		parent::configure();
	}

}
