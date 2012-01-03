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

	/**
	 * @var string
	 */
	public $noResultsTemplateFilename;

	/**
	 * @var int
	 */
	public $resultsPerPage = 10;

	/**
	 * @var string
	 */
	public $controllerClass;

	/**
	 * Main method
	 */
	public function configure()
	{
		if (empty($this->controllerClass)) {
			$this->controllerClass = SearchController::CN();
		}

		if (empty($this->title)) {
			$this->title = 'Search';
		}
		
		$this->group = 'System';
		
		if(empty($this->description)) {
			$this->description = 'Search controller';
		}
		
		$this->cmsClassname = 'Editable';
		
		$this->iconWebPath = '/assets/img/blocks/system_block.png';

		parent::configure();
	}

}
