<?php

namespace Supra\Database\Doctrine\Functions;

use Doctrine\ORM\Query\Lexer,
		Doctrine\ORM\Query\AST\Functions\FunctionNode,
		Doctrine\ORM\Query\SqlWalker,
		Doctrine\ORM\Query\Parser,
		Doctrine\ORM\Query\AST\ConditionalPrimary;

/**
 * Function IF
 */
class IfFunction extends FunctionNode
{
	/**
	 * @var ConditionalPrimary
	 */
	private $condition;
	
	private $yes;
	
	private $no;
	
	public function getSql(SqlWalker $sqlWalker)
	{
		$if = 'CASE WHEN ' . $sqlWalker->walkConditionalPrimary($this->condition) 
				. ' THEN ' . $sqlWalker->walkStringPrimary($this->yes)
				. ' ELSE ' . $sqlWalker->walkStringPrimary($this->no)
				. ' END';
		
		return $if;
	}

	public function parse(Parser $parser)
	{
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		
		$this->condition = $parser->ConditionalPrimary();
		
		$parser->match(Lexer::T_COMMA);
		
		$this->yes = $parser->ArithmeticExpression();
		
		$parser->match(Lexer::T_COMMA);
		
		$this->no = $parser->ArithmeticExpression();
		
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
}
