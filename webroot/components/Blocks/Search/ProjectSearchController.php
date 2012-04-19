<?php

namespace Project\Blocks\Search;

use Supra\Search\SearchController;

class ProjectSearchController extends SearchController
{
	const PROPERTY_NAME_RESULT_LIST_TITLE = 'resultListTitle';
	const PROPERTY_NAME_RESULTS_NOTHING_FOUND = 'nothingFoundMessage';
	const PROPERTY_NAME_RESULTS_NEXT_PAGE = 'nextPageTitle';
	const PROPERTY_NAME_RESULTS_PREVIOUS_PAGE = 'previousPageTitle';
	const PROPERTY_NAME_RESULTS_ERROR = 'errorMessage';
	
	const PROPERTY_NAME_INVITATION = 'invitationToSearch';
	const PROPERTY_NAME_BUTTON_CAPTION = 'buttonCaption';
	const PROPERTY_NAME_SEARCHED_FOR_LABEL = 'searchedForLabel';
	const PROPERTY_NAME_NUMBER_OF_RESULTS_LABEL = 'numberOfResultsLabel';
	const PROPERTY_NAME_ERROR = 'errorMessage';	
	
	public static function getPropertyDefinition()
	{
		$contents = array();

		$item = new \Supra\Editable\String('Results list title');
		$item->setDefaultValue('Meklēšanas rezultāti');
		$contents[self::PROPERTY_NAME_RESULT_LIST_TITLE] = $item;
		
		$item = new \Supra\Editable\Html('"Nothing found" message');
		$item->setDefaultValue('Netika atrasts neviens ieraksts.<br />Pārbaudiet meklējamo vārdu vai frāzi un atkārtojiet meklēšanu.');
		$contents[self::PROPERTY_NAME_RESULTS_NOTHING_FOUND] = $item;
		
		$item = new \Supra\Editable\String('Message to show in case of error');
		$item->setDefaultValue('Meklēšanas kļūda. Lūdzu mēģiniet vēlāk.');
		$contents[self::PROPERTY_NAME_RESULTS_ERROR] = $item;
		
		$item = new \Supra\Editable\String('"Next page" title');
		$item->setDefaultValue('Atpakaļ');
		$contents[self::PROPERTY_NAME_RESULTS_NEXT_PAGE] = $item;
		
		$item = new \Supra\Editable\String('"Previous page" title');
		$item->setDefaultValue('Tālāk');
		$contents[self::PROPERTY_NAME_RESULTS_PREVIOUS_PAGE] = $item;
		
		// ---
		
		$item = new \Supra\Editable\String('Invitation to search');
		$item->setDefaultValue('Lūdzu ievadiet meklējamo vārdu vai frāzi');
		$contents[self::PROPERTY_NAME_INVITATION] = $item;
		
		$item = new \Supra\Editable\String('Search button caption');
		$item->setDefaultValue('Meklēt');
		$contents[self::PROPERTY_NAME_BUTTON_CAPTION] = $item;
		
		$item = new \Supra\Editable\String('"Number of results" label');
		$item->setDefaultValue('Atrasto pozīciju skaits');
		$contents[self::PROPERTY_NAME_NUMBER_OF_RESULTS_LABEL] = $item;
		
		$item = new \Supra\Editable\String('"Searched for" label');
		$item->setDefaultValue('Meklēts pēc');
		$contents[self::PROPERTY_NAME_SEARCHED_FOR_LABEL] = $item;
		
		$item = new \Supra\Editable\String('Message to show in case of error');
		$item->setDefaultValue('Meklēšanas kļūda. Lūdzu mēģiniet vēlāk.');
		$contents[self::PROPERTY_NAME_ERROR] = $item;

		return $contents;
	}
}
