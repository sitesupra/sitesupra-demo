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
	 * Raw content
	 * @var string
	 */
	protected $content;
	
	/**
	 * Content type
	 * @var int
	 */
	protected $type;
	
	
	
	/**
	 * replacement tag => assigned subscriber object method
	 * @var array
	 */
	protected $commonReplacements = array('subscriberName' => 'getName',
										 'subscriberEmail' => 'getEmailAddress');


	public function __construct($type, $content){
	
		$this->type = (int) $type;
		$this->content = $content;
		
	}
	
	
	
	
	/**
	 * Prepare (make replacements) content
	 * @param Entity\Subscriber $subscriber
	 * @return string
	 */
	public function getPreparedContent(Entity\Subscriber $subscriber){

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
		
		$preparedContent = str_replace($search, $replace, $this->content);
		
		
		/**
		 * @todo implement some content type-depended replacements
		 */
		
		switch($this->type) {
		
			case self::TYPE_HTML_CONTENT: {
				
			}break;
			
			case self::TYPE_TEXT_CONTENT: {
				
			}break;
		
			case self::TYPE_SUBJECT: {
				
			}break;
		}
		
		return $preparedContent;
	}
	
	
	
	
}

