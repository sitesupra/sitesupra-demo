<?php

namespace Project\Blocks\Feedback;

use Supra\Controller\Pages\BlockController;
use Supra\Editable;
use Supra\Uri\Path;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Validator\Type\AbstractType;
use Supra\Validator\Exception\ValidationFailure;
use Supra\Controller\Pages\Request\PageRequestView;

/**
 * Feedback block
 */
class FeedbackBlock extends BlockController
{
	const GROUP_ERRORS = 'Error messages';
	const GROUP_LABELS = 'Labels';
	const GROUP_INPUTS = 'Input settings';
	const GROUP_DROPDOWN = 'Subjects dropdown values';
	
	const AJAX_ACTION_PATH = '/ajax/feedback';
	const CAPTCHA_CLASSNAME = 'Project\Blocks\Captcha\Captcha';
	
	/**
	 * Form inputs are defined here
	 * 
	 * Available "types" are:
	 *		- text
	 *		- textarea
	 *		- dropdown
	 *		- submit (button)
	 *		- button
	 *		- file | @TODO: not implemented
	 *  
	 * Available validation TYPES are:
	 *		- "any", means any content
	 *		- "email", email specific validation
	 *		- "phone", phone number specific validation, see php code below
	 *		- "captcha", captcha image validation
	 *		- "text", text only | @TODO: not implemented
	 *		- "numbers", numbers only | @TODO: not implemented
	 * 
	 * Available validation parameters are:
	 *		- "minLength"(int), means that content MIN length will be checked
	 *		- "maxLength"(int), means that content MAX length will be checked 
	 *		- "required"(bool), specifies, if field is not allowed to be empty or not
	 * 
	 * "name" is shown as label in block settings in BO
	 * "labelValue" is set as default block property value
	 * array item key is used as input ID
	 * 
	 * @var array
	 */
	protected $formInputs = array(
		'name' => array(
			'type' => 'text',
			'name' => 'Name',
			'labelValue' => 'Vārds',
			
			'validation' => array(
				'minLength' => 2,
			),
		),
		
		'email' => array(
			'type' => 'text',
			'name' => 'Email',
			'labelValue' => 'E-pasts',
			
			'validation' => array(
				'type' => 'email',
				'required' => true,
				'requiredDependsOn' => 'phone',
			),
		),
		
		'phone' => array(
			'type' => 'text',
			'name' => 'Phone number',
			'labelValue' => 'Tālrunis',
			
			'validation' => array(
				'type' => 'phone',
				'required' => true,
				'requiredDependsOn' => 'email',
			),
		),
		
		'subject' => array(
			'type' => 'dropdown',
			'optionsCount' =>  10,
			'name' => 'Subject',
			'labelValue' => 'Izvelēties tēmu',
			
			'validation' => array(
				'required' => true,
			),
		),
		
		'question' => array(
			'type' => 'textarea',
			'name' => 'Question',
			'labelValue' => 'Jautājums',
			
			'validation' => array(
				'required' => true,
			),
		),
		
		'captcha' => array(
			'type' => 'captcha',
			'name' => 'Captcha',
			'labelValue' => 'Ievadiet grafisko kodu',
			
			'validation' => array(
				'type' => 'captcha',
			),
		),
		
		'submit' => array(
			'type' => 'submit',
			'name' => 'Submit button',
			'labelValue' => 'Nosūtīt',
		),
		
		'close' => array(
			'type' => 'button',
			'name' => 'Close button',
			'labelValue' => 'Aizvērt',
		),
	);
	
	/**
	 * Main method
	 */
	public function doExecute()
	{
		$response = $this->getResponse();
		
		$localization = $this->getRequest()
				->getPageLocalization();
		
		$pagePath = null;
		if ($localization instanceof \Supra\Controller\Pages\Entity\PageLocalization) {
			$pagePath = $localization->getPath()
					->getFullPath(Path::FORMAT_BOTH_DELIMITERS);
		}
		
		$errorLabels = array();
		foreach($this->formInputs as $id => $input) {
			$errorLabels[$id] = $this->getValidationErrorLabels($input);
		}
		
		$response->assign('errorLabels', $errorLabels);
		$response->assign('formInputs', $this->formInputs);
		$response->assign('requestPath', self::AJAX_ACTION_PATH);
		
		$blockId = $this->getBlock()
				->getId();
		
		$response->assign('blockId', $blockId);
		
		$context = $response->getContext();
		$context->addJsUrlToLayoutSnippet('js', "http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js");
		
		if ($this->getRequest() instanceof PageRequestView) {
			$context->addJsUrlToLayoutSnippet('js', "/components/Feedback/feedback.js");
		}
		
		$context->addCssLinkToLayoutSnippet('js', "/components/Feedback/feedback.css");		
		
		$response->outputTemplate('index.html.twig');
	}
	
	/**
	 * Returns available block properties
	 * using defined $formInputs (labels, validation errors) and also labels for
	 * send/close buttons and default form success message
	 * 
	 * @return array
	 */
	public function getPropertyDefinition()
	{
		$properties = array();
		
		foreach($this->formInputs as $id => $input) {
			
			// Inputs labels
			$propertyName = $input['name'] . ' label';
			
			$label = new Editable\String($propertyName, self::GROUP_LABELS);
			if ( isset($input['labelValue'])) {
				$label->setDefaultValue($input['labelValue']);
			}
			$properties[ $propertyName ] = $label;
			
			if ($input['type'] == 'dropdown') {
				$groupLabel = '"' . $input['name'] . '"' . ' dropdown options';
				
				//$label = new Editable\Number("{$input['name']} options count", $groupLabel);
				//$label->setDefaultValue(0);
				//$properties[ "{$input['name']} options count" ] = $label;
				
				//$optionsCount = (int)$this->getBlockPropertyRawValue("{$input['name']} options count");
				
				//for($i = 1; $i <= $optionsCount; $i++) {
				for($i = 1; $i <= $input['optionsCount']; $i++) {
					$label = new Editable\String( $input['name'] . " {$i}. option", $groupLabel);
					$label->setDefaultValue("{$i}. option");
					$properties[ $input['name'] . " {$i}. option" ] = $label;
				}
			}
			
			// If input is a captcha, then we need to add additional label for 'reload' link
			if ($input['type'] == 'captcha') {
				
				// check, if captcha is available
				if ( ! class_exists(self::CAPTCHA_CLASSNAME, false)) {
					continue;
				}
				
				$label = new Editable\String('Reload ' . $propertyName, self::GROUP_LABELS);
				$label->setDefaultValue('Cits');
				$properties[ 'Reload ' . $propertyName ] = $label;
			}
			
			// Inputs controls
			$propertyName = $input['name'] . ' field';
			
			$label = new Editable\Checkbox($propertyName, self::GROUP_INPUTS);
			$label->setDefaultValue(true);
			
			$properties[ $propertyName ] = $label;
			
			// Inputs validation failure messages
			if (isset($input['validation'])) {
				
				$errorLabels = self::getValidationErrorLabels($input);
				foreach ($errorLabels as $errorType => $labelValue) {
					$propertyName = "{$input['name']} {$errorType} error";
					
					$label = new Editable\String($propertyName, self::GROUP_ERRORS);
					$label->setDefaultValue($labelValue);

					$properties[ $propertyName ] = $label;
				}
			
			}
					
		}
		
		// Additional properties
		//     success message
		$editable = new Editable\Html('Success message');
		$editable->setDefaultValue('<b>Paldies!</b> Jūsu atsauksme ir nosūtīta.');
		$properties['successMessage'] = $editable;
		
		return $properties;
	}

	/**
	 * @return array
	 */
	public function getFormInputs()
	{
		return $this->formInputs;
	}
	
	/**
	 * Default values for validation errors
	 * 
	 * @param array $input
	 * @return array
	 */
	private function getValidationErrorLabels(array $input) {
		
		$labels = array();
		
		$validation = &$input['validation'];
		
		if ( ! isset($validation['type'])) {
			$validation['type'] = 'any';
		}
		
		// type specific validation errors
		switch ($validation['type']) {
			case 'email': 
				$labels['invalid'] = 'Lūdzu, ievadiet pareizu e-pasta adresi';
				break;
			case 'phone':
				$labels['invalid'] = 'Lūdzu, ievadiet pareizu tālruņa numuru. Numurs var saturēt tikai ciparus un simbolus „ ”, „+”, „(”, „)”';
				break;
			case 'captcha': 
				$labels['invalid'] = 'Grafiskais kods ir ievadīts nepareizi';
				break;
		}
		
		// minimal content length
		if (isset($validation['minLength'])) {
			$labels['minLength'] = "Jāievada vismaz {$validation['minLength']} simboli";
		}
		
		// maximal content length
		if (isset($validation['maxLength'])) {
			$labels['maxLength'] = "Jāievada ne vairāk kā {$validation['maxLength']} simboli";
		}
		
		// field is required
		if (isset($validation['required']) && $validation['required']) {
			switch ($input['type']) {
				case 'dropdown': 
					$value = 'Lūdzu izvēlieties vienu no izvelnes opcijām';
					break;
				
				case 'file':
					$value = 'Fails nav izvēlēts';
				
				default: 
					$value = 'Lūdzu aizpildiet šo laukumu';
			}
			
			$labels['required'] = $value;
		}
		
		return $labels;
	}

}
