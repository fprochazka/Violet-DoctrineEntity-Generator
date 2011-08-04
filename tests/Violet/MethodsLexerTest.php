<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Testing\Violet;

use Kdyby;
use Kdyby\Violet\Method;
use Nette;



/**
 * @author Filip Procházka
 */
class MethodsLexerTest extends \PHPUnit_Framework_TestCase
{

	/** @var Kdyby\Violet\MethodsLexer */
	private $lexer;



	public function setUp()
	{
		$this->lexer = new Kdyby\Violet\MethodsLexer();
	}



	/**
	 * @param array $actualMethods
	 * @return Method
	 */
	private function extractMethod($actualMethods)
	{
		$this->assertInternalType('array', $actualMethods, "returned array");
		$this->assertSame(1, count($actualMethods), "returned only one method");
		$method = reset($actualMethods);
		$this->assertInstanceOf('Kdyby\Violet\Method', $method);

		return $method;
	}



	/**
	 * @param string $expectedName
	 * @param array $actualMethods
	 */
	protected function assertMethodName($expectedName, $actualMethods)
	{
		$method = $this->extractMethod($actualMethods);
		$this->assertSame($expectedName, $method->name);
	}



	/**
	 * @param string $expectedVisibility
	 * @param array $actualMethods
	 */
	protected function assertMethodVisibility($expectedVisibility, $actualMethods)
	{
		$method = $this->extractMethod($actualMethods);
		$this->assertSame($expectedVisibility, $method->visibility);
	}



	/**
	 * @param string $expectedType
	 * @param string $expectedSubtype
	 * @param array $actualMethods
	 */
	protected function assertMethodReturnType($expectedType, $expectedSubtype, $actualMethods)
	{
		$method = $this->extractMethod($actualMethods);
		$this->assertSame($expectedType, $method->returns);

		if ($expectedSubtype !== NULL) {
			$this->assertSame($expectedSubtype, $method->returnsSubtype);
		}
	}



	/**
	 * @param array $expectedArguments,
	 * @param array $actualMethods
	 */
	protected function assertMethodArguments(array $expectedArguments, $actualMethods)
	{
		$method = $this->extractMethod($actualMethods);
		$this->assertSame($expectedArguments, $method->args);
	}



	public function testParsingName()
	{
		$actual = $this->lexer->parse('chuckNorris()');

		$this->assertMethodName('chuckNorris', $actual);
	}



	public function testParsingVisibilityPublic()
	{
		$actual = $this->lexer->parse('+ chuckNorris()');

		$this->assertMethodName('chuckNorris', $actual);
		$this->assertMethodVisibility(Method::VISIBILITY_PUBLIC, $actual);
	}



	public function testParsingVisibilityProtected()
	{
		$actual = $this->lexer->parse('# chuckNorris()');

		$this->assertMethodName('chuckNorris', $actual);
		$this->assertMethodVisibility(Method::VISIBILITY_PROTECTED, $actual);
	}



	public function testParsingVisibilityPrivate()
	{
		$actual = $this->lexer->parse('- chuckNorris()');

		$this->assertMethodName('chuckNorris', $actual);
		$this->assertMethodVisibility(Method::VISIBILITY_PRIVATE, $actual);
	}



	public function testParsingReturnType()
	{
		$actual = $this->lexer->parse('chuckNorris() : string');

		$this->assertMethodName('chuckNorris', $actual);
		$this->assertMethodReturnType('string', NULL, $actual);
	}



	public function testParsingReturnSubtype()
	{
		$actual = $this->lexer->parse('chuckNorris() : Collection<string>');

		$this->assertMethodName('chuckNorris', $actual);
		$this->assertMethodReturnType('Collection', 'string', $actual);
	}



	public function testParsingArguments()
	{
		$actual = $this->lexer->parse('chuckNorris(string foo, integer bar)');

		$this->assertMethodName('chuckNorris', $actual);
		$this->assertMethodArguments(array(
			'foo' => 'string',
			'bar' => 'integer'
		), $actual);
	}

}