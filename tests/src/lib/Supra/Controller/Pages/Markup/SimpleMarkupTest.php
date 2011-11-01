<?php

namespace Supra\Tests\Controller\Pages\Markup;

use Supra\Controller\Pages\Markup\Tokenizer;
use Supra\Controller\Pages\Markup\Abstraction\SupraMarkupElement;
use Supra\Controller\Pages\Markup\Abstraction\ElementAbstraction;
use Supra\Controller\Pages\Markup\HtmlElement;

class BasicMarkiupTest extends \PHPUnit_Framework_TestCase
{

	private $source;

	public function setUp()
	{
		$this->source = '<p>Bafjotn {sssusus.lsdsf /} {supra.link id="su999"}byjukych ofpen zojuhtihaw xiofdozji. Czork nudsij mndugboqok zzylox etloduv awpinnelq iblacd iwjoza. Dnmku fubjr ykimoqjeng egtic repao hyrbusqoky wfhomhryx ukuky ksuxrqu. </p><p>Mhdva zcguxbkufw tepmakitd nydrbatpup tolpud bibodebsnq omonvr. Ufohz mysfllirha wkzebbapj ajaleqqeu biwajguszp rosqoxvg kawmeju jmyewbolri. Vocfacizgq eqsrush wymaux cgypni vozjm syhbsusr ifoqacqd. Ipipum escgiflu uroimzy gdgepu rlmoleuzl owwecil qvoky gejtkbexi. {supra.link id="su2"}Dccderwfo gyzxx hqykebabj{/supra.link} apemilsi defwk. Dyxstefwfa kopkoht idupwd ofgjs uvobwh udvunyaqqu yttaldrqa nagpip. </p><p><br></p><p>{supra.image id="su1"/}</p><p><br></p><p>Uzlafebl eftqxo yhtok ptxugexu ikxytswe </p>{supra.link id="su6"}ngawozuzisduh syvlf ocm{supra.image id="su7"/} {/supra.link}<p>p anpueka rtxqyh vryqug.&nbsp;.&nbsp;</p><p>Qeslax yiqkluy tgysgdohyk imnteav1vt nodzyzv. Ipywrhkxi fy. </p><p></p>';
	}

	function testSplitter()
	{
		$t = new Tokenizer($this->source);
		
		$t->tokenize();
		
		$elements = $t->getElements();
		
		foreach($elements as $element) {
			/* @var $element ElementAbstraction */
			
			if($element instanceof SupraMarkupElement) {
				\Log::debug($element->getSignature());
			}
			else if($element instanceof HtmlElement) {
				\Log::debug('CONTENT ', $element->getContent());
			}
		}
		
	}
	
	function testSystemBraceUsage()
	{
		$t = new Tokenizer('{');
		$t->tokenize();
	}
	
//	function testImage()
//	{
//		$t = new Tokenizer('Empty{supra.image id="su7"}link');
//		$t->tokenize();
//		
//		$elements = $t->getElements();
//		
//		self::assertEquals(3, count($elements));
//	}
//	
//	function testEmptyLink()
//	{
//		$t = new Tokenizer('Empty{supra.link id="su7"}link');
//		$t->tokenize();
//		
//		$elements = $t->getElements();
//		
//		self::assertEquals(3, count($elements));
//	}
}