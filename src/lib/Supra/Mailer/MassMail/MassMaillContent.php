<?php

namespace Supra\Mailer\MassMail;

use Supra\ObjectRepository\ObjectRepository;
use Supra\Mailer\MassMail\Entity;

class MassMaillContent
{

	const TYPE_HTML_CONTENT = 10;
	const TYPE_TEXT_CONTENT = 20;
	const TYPE_SUBJECT = 30;
	
	/**
	 * replacement tag => assigned subscriber object method
	 * @var array
	 */
	protected $commonReplacements = array('subscriberName' => 'getName',
										 'subscriberEmail' => 'getEmailAddress');

	
	/**
	 * Prepare (make replacements) content
	 * @param string $content
	 * @param int $type
	 * @param Entity\Subscriber $subscriber
	 * @return string
	 */
	public function prepareContent(	$content, 
									$type, 
									Entity\Subscriber $subscriber){

		/**
		 * @todo make replacements for some custom fields
		 */
		
		$search = array();
		$replace = array();
		
		foreach($this->commonReplacements as $k => $v){
			$search[] = '[[' . $k . ']]';
			$replace = $subscriber->$v;
		}
		
		//common replacement
		
		$content = str_replace($search, $replace, $content);
		
		
		/**
		 * @todo implement some content type-depended replacements
		 */
		
		switch($type) {
		
			case self::TYPE_HTML_CONTENT: {
				
			}break;
			
			case self::TYPE_TEXT_CONTENT: {
				
			}break;
		
			case self::TYPE_SUBJECT: {
				
			}break;
		}
		
		return $content;
	}
	
	
	
	
}

