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
use Kdyby\Violet\Property;
use Nette;



/**
 * @author Filip Procházka
 */
class PropertiesLexerTest extends \PHPUnit_Framework_TestCase
{

	/** @var Kdyby\Violet\PropertiesLexer */
	private $lexer;



	public function setUp()
	{
		$this->lexer = new Kdyby\Violet\PropertiesLexer();
	}



	/**
	 * @param array $actualProperties
	 * @return Property
	 */
	private function extractProperty($actualProperties)
	{
		$this->assertInternalType('array', $actualProperties, "returned array");
		$this->assertSame(1, count($actualProperties), "returned only one property");
		$property = reset($actualProperties);
		$this->assertInstanceOf('Kdyby\Violet\Property', $property);

		return $property;
	}



	/**
	 * @param string $expectedName
	 * @param array $actualProperties
	 */
	protected function assertPropertyName($expectedName, $actualProperties)
	{
		$property = $this->extractProperty($actualProperties);
		$this->assertSame($expectedName, $property->name);
	}



	/**
	 * @param string $expectedVisibility
	 * @param array $actualProperties
	 */
	protected function assertPropertyVisibility($expectedVisibility, $actualProperties)
	{
		$property = $this->extractProperty($actualProperties);
		$this->assertSame($expectedVisibility, $property->visibility);
	}



	/**
	 * @param string $expectedType
	 * @param string $expectedSubype
	 * @param array $actualProperties
	 */
	protected function assertPropertyType($expectedType, $expectedSubype, $actualProperties)
	{
		$property = $this->extractProperty($actualProperties);
		$this->assertSame($expectedType, $property->type);

		if ($expectedSubype !== NULL) {
			$this->assertSame($expectedSubype, $property->subtype);
		}
	}



	/**
	 * @param string $expectedDefaultValue
	 * @param array $actualProperties
	 */
	protected function assertPropertyDefaultValue($expectedDefaultValue, $actualProperties)
	{
		$property = $this->extractProperty($actualProperties);
		$this->assertSame($expectedDefaultValue, $property->defaultValue);
	}



	public function testParsingName()
	{
		$actual = $this->lexer->parse('chuckNorris');

		$this->assertPropertyName('chuckNorris', $actual);
	}



	public function testParsingVisibilityPublic()
	{
		$actual = $this->lexer->parse('+ chuckNorris');

		$this->assertPropertyName('chuckNorris', $actual);
		$this->assertPropertyVisibility(Property::VISIBILITY_PUBLIC, $actual);
	}



	public function testParsingVisibilityProtected()
	{
		$actual = $this->lexer->parse('# chuckNorris');

		$this->assertPropertyName('chuckNorris', $actual);
		$this->assertPropertyVisibility(Property::VISIBILITY_PROTECTED, $actual);
	}



	public function testParsingVisibilityPrivate()
	{
		$actual = $this->lexer->parse('- chuckNorris');

		$this->assertPropertyName('chuckNorris', $actual);
		$this->assertPropertyVisibility(Property::VISIBILITY_PRIVATE, $actual);
	}



	public function testParsingType()
	{
		$actual = $this->lexer->parse('chuckNorris : string');

		$this->assertPropertyName('chuckNorris', $actual);
		$this->assertPropertyType('string', NULL, $actual);
	}



	public function testParsingSubtype()
	{
		$actual = $this->lexer->parse('chuckNorris : Collection<string>');

		$this->assertPropertyName('chuckNorris', $actual);
		$this->assertPropertyType('Collection', 'string', $actual);
	}



	public function testParsingDefaultValue()
	{
		$actual = $this->lexer->parse('chuckNorris = FALSE');

		$this->assertPropertyName('chuckNorris', $actual);
		$this->assertPropertyDefaultValue('FALSE', $actual);
	}

}