<?php

namespace Supra\Package\Cms\Pages\Markup;

use Supra\Loader\Loader;

abstract class TokenizerAbstraction
{

	/**
	 * @var string
	 */
	protected $source;

	/**
	 * @var boolean
	 */
	protected $truncateInvalidBlocks;

	/**
	 * @var array of Abstraction/ElementAbstraction
	 */
	protected $elements = array();

	/**
	 * @var array of string
	 */
	protected $markupElements = array();

	function __construct($source)
	{
		$this->truncateInvalidBlocks = true;
		$this->source = $source;
	}

	/**
	 * Returns regular expressiont to be used for matching for knwon signatures.
	 * @return string
	 */
	protected function getSignaturesRegexp()
	{
		$signatures = array_map('preg_quote', array_keys($this->markupElements));

		$signaturesRegexp = '(?:' . join(')|(?:', $signatures) . ')';

		return $signaturesRegexp;
	}

	/**
	 * Sets invalid block trucation option.
	 * @param boolean $truncateInvalidBlocks 
	 */
	public function setTruncateInvalidBlocks($truncateInvalidBlocks)
	{
		$this->truncateInvalidBlocks = $truncateInvalidBlocks;
	}

	/**
	 * Returns tokenized content.
	 * @return array of Abstraction/ElementAbstraction
	 */
	public function getElements()
	{
		return $this->elements;
	}

	/**
	 * Returns element signature from splitted fragment.
	 * @param type $elementString
	 * @return string
	 */
	protected function extractSignature($elementString)
	{
		$match = array();

		$regexp = '@\{/?(' . $this->getSignaturesRegexp() . ').*?/?\}@ims';

		preg_match($regexp, $elementString, $match);

		if (empty($match[1])) {
			return null;
			//throw new Exception\RuntimeException('Could not extract signature from "' . $elementString . '"');
		}
		
		return $match[1];
	}

	/**
	 * Splits $this->source into elements.
	 */
	public function splitSource()
	{
		$splitterRegexp = '@(\{/?(?:' . $this->getSignaturesRegexp() . ').*?/?\})@ims';

		$elementsRaw = preg_split($splitterRegexp, $this->source, -1, 
				PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY);

		/* @var $element Abstraction\ElementAbstraction */
		$this->elements = array();

		foreach ($elementsRaw as $rawElement) {
			
			$elementSource = $rawElement[0];
			
			$firstCharOfElementSource = $elementSource{0};
			$secondCharOfElementSource = strlen($elementSource) > 1 ? $elementSource{1} : null;
			$preLastCharOfElementSource = strlen($elementSource) > 2 ? $elementSource{strlen($elementSource) - 2} : null;
			
			$element = true;
			$signature = null;

			// If this is SupraMarkup element, try to extract signature from raw element, ...
			if ($firstCharOfElementSource == '{') {
				$signature = $this->extractSignature($elementSource);
			}

			if ( ! empty($signature)) {

			 // create element from signature, ...
				$elementClassName = $this->markupElements[$signature];
				
				$element = Loader::getClassInstance(
						$elementClassName, 
						Abstraction\SupraMarkupElement::CN()
				);

				/* @var $element Abstraction\ElementAbstraction */

				if ($preLastCharOfElementSource == '/') {
					// ... and if this is a standalone markup element - like {trololo.trololo /}, 
					// create and initialize it.

					$element->setSource($elementSource);
					$element->parseSource();
				}
				else if ($secondCharOfElementSource == '/') {
					// ... or if this this element is a closing part of a block, 
					// i.e. - begins with a slash, check if class of instace we 
					// created earlier actually is block constructor. If it is so,
					// treat created element as block constructor and get block 
					// end element from it. Otherwise do nothing, skip this element.

					if($element instanceof Abstraction\SupraMarkupBlockConstructor) {
						/* @var $element Abstraction\SupraMarkupBlockConstructor */
						$element = $element->makeEnd();
						$this->elements[] = $element;
					}
				}
				else {
					// ... otherwise this looks like an opening part of a block. We check if 
					// instace of this element is subclass of SupraMarkupBlockConstructor, then proceed to 
					// fetch coresponding block start element. Otherwise use element already created. This is because 
					// there might be some errornous SupraMarkup cases when, for exampl {supra.image ...} does not 
					// have a slash in the end.

					if($element instanceof Abstraction\SupraMarkupBlockConstructor) {
						/* @var $element Abstraction\SupraMarkupBlockConstructor */
						$element = $element->makeStart();
					}

					$element->setSource($elementSource);
					$element->parseSource();
					
					$this->elements[] = $element;
				}
			}
			else {
				// If is just a piece of content (HTML), just create element and set it's content.

				$element = new HtmlElement();
				$element->setContent($elementSource);
				
				$this->elements[] = $element;
			}
		}
	}

	/**
	 * Links block starts and block ends - calls setStart() and setEnd() on respective ends of block.
	 * @param type $elements
	 * @return array 
	 */
	function linkBlocks()
	{
		$ends = array();

		$elements = $this->elements;

		$result = array();
		while ($element = array_pop($elements)) {

			if ($element instanceof Abstraction\SupraMarkupBlockStart) {

				/* @var $end Abstraction\SupraMarkupBlockEnd */
				$end = array_pop($ends);


				// Check if we hava a matching end for this start and check if their signature match,
				// or skip this check if truncation of invalid blocks is disabled.
				if (
						( ! empty($end) && $end->getSignature() == $element->getSignature() ) ||
						$this->truncateInvalidBlocks == false
				) {
					
					$end->setStart($element);
					$element->setEnd($end);

					array_unshift($result, $element);
				}
			}
			else if ($element instanceof Abstraction\SupraMarkupBlockEnd) {

				array_unshift($result, $element);
				$ends[] = $element;
			}
			else {
				array_unshift($result, $element);
			}
		}

		$this->elements = $result;
	}

	public function tokenize()
	{
		$this->splitSource();
		$this->linkBlocks();
	}

}

