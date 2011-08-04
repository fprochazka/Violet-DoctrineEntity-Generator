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
use Kdyby\Violet\ClassType;
use Kdyby\Violet\Property;
use Nette;



/**
 * @author Filip Procházka
 */
class PropertyTest extends \PHPUnit_Framework_TestCase
{

	/** @var array */
	private $types = array();



	public function setUp()
	{
		$relationshipsTestFile = __DIR__ . '/relationstest.class.violet';
		if (!file_exists($relationshipsTestFile)) {
			throw new Nette\IOException("File $relationshipsTestFile is not readable");
		}

		$reader = new Kdyby\Violet\ClassDiagramReader(file_get_contents($relationshipsTestFile));
		$this->types = $reader->getTypes();
	}



	/**
	 * @param string $typeName
	 * @param string $propertyName
	 * @return Property
	 */
	private function getPropertyOfType($typeName, $propertyName)
	{
		if (!isset($this->types[$typeName])) {
			throw new Nette\InvalidArgumentException("Type '$typeName' not found");
		}

		$type = $this->types[$typeName];

		foreach ($type->properties as $property) {
			if ($property->name === $propertyName) {
				return $property;
			}
		}

		throw new Nette\InvalidArgumentException("Type '$typeName' has no property '$propertyName'");
	}



	public function testOneToOneUnidirectional()
	{
		$this->assertFalse($this->getPropertyOfType('Five', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Five', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Five', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Five', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Six', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Six', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Six', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Six', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Five', 'six')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Five', 'six')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Five', 'six')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Six',
				'@OneToOne(targetEntity="Six")',
				'@JoinColumn(name="six_id", referencedColumnName="id")'
			), $this->getPropertyOfType('Five', 'six')->getAnnotations());
	}



	public function testOneToOneBidirectional()
	{
		$this->assertFalse($this->getPropertyOfType('Seven', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Seven', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Seven', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Seven', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Eight', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Eight', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Eight', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Eight', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Seven', 'eight')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Seven', 'eight')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Seven', 'eight')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Eight',
				'@OneToOne(targetEntity="Eight", mappedBy="seven")'
			), $this->getPropertyOfType('Seven', 'eight')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Eight', 'seven')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Eight', 'seven')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Eight', 'seven')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Seven',
				'@OneToOne(targetEntity="Seven", inversedBy="eight")',
				'@JoinColumn(name="seven_id", referencedColumnName="id")'
			), $this->getPropertyOfType('Eight', 'seven')->getAnnotations());
	}



	public function testOneToOneSelfReferencing()
	{
		$this->assertFalse($this->getPropertyOfType('Twelve', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Twelve', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Twelve', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Twelve', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Twelve', 'twelve')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Twelve', 'twelve')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Twelve', 'twelve')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Twelve',
				'@OneToOne(targetEntity="Twelve")',
				'@JoinColumn(name="twelve_id", referencedColumnName="id")'
			), $this->getPropertyOfType('Twelve', 'twelve')->getAnnotations());
	}



	public function testOneToManyUnidirectionWithJoinRable()
	{
		$this->assertFalse($this->getPropertyOfType('Two', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Two', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Two', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Two', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('One', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('One', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('One', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('One', 'name')->getAnnotations());

		$this->assertTrue($this->getPropertyOfType('One', 'twos')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('One', 'twos')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('One', 'twos')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Collection',
				'@ManyToMany(targetEntity="Two")',
				'@JoinTable(name="ones_twos", ' .
						'joinColumns={@JoinColumn(name="two_id", referencedColumnName="id"), unique=TRUE}, ' .
						'inverseJoinColumns={@JoinColumn(name="one_id", referencedColumnName="id")}' .
					')'
			), $this->getPropertyOfType('One', 'twos')->getAnnotations());
	}



	public function testManyToOneUnidirectional()
	{
		$this->assertFalse($this->getPropertyOfType('Thirteen', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Thirteen', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Thirteen', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Thirteen', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Fourteen', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Fourteen', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Fourteen', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Fourteen', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Fourteen', 'thirteen')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Fourteen', 'thirteen')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Fourteen', 'thirteen')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Thirteen',
				'@ManyToOne(targetEntity="Thirteen")',
				'@JoinColumn(name="thirteen_id", referencedColumnName="id")'
			), $this->getPropertyOfType('Fourteen', 'thirteen')->getAnnotations());
	}



	public function testOneToManyBidirectional()
	{
		$this->assertFalse($this->getPropertyOfType('Three', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Three', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Three', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Three', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Four', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Four', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Four', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Four', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Four', 'three')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Four', 'three')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Four', 'three')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Three',
				'@ManyToOne(targetEntity="Three", inversedBy="fours")',
				'@JoinColumn(name="three_id", referencedColumnName="id")'
			), $this->getPropertyOfType('Four', 'three')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Three', 'fours')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Three', 'fours')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Three', 'fours')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Collection',
				'@OneToMany(targetEntity="Four", mappedBy="three")'
			), $this->getPropertyOfType('Three', 'fours')->getAnnotations());
	}



	public function testOneToManySelfReferencing()
	{
		$this->assertFalse($this->getPropertyOfType('Nine', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Nine', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Nine', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Nine', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Nine', 'children')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Nine', 'children')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Nine', 'children')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Collection',
				'@OneToMany(targetEntity="Nine", mappedBy="parent")'
			), $this->getPropertyOfType('Nine', 'children')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Nine', 'parent')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Nine', 'parent')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Nine', 'parent')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Nine',
				'@ManyToOne(targetEntity="Nine", inversedBy="children")',
				'@JoinColumn(name="parent_id", referencedColumnName="id")'
			), $this->getPropertyOfType('Nine', 'parent')->getAnnotations());
	}



	public function testManyToManyUnidirectional()
	{
		$this->assertFalse($this->getPropertyOfType('Fiveteen', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Fiveteen', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Fiveteen', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Fiveteen', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Sixteen', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Sixteen', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Sixteen', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Sixteen', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Fiveteen', 'sixteens')->isOneToManyUnidirectional());
		$this->assertTrue($this->getPropertyOfType('Fiveteen', 'sixteens')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Fiveteen', 'sixteens')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Collection',
				'@ManyToMany(targetEntity="Sixteen")',
				'@JoinTable(name="fiveteens_sixteens", ' .
						'joinColumns={@JoinColumn(name="sixteen_id", referencedColumnName="id")}, ' .
						'inverseJoinColumns={@JoinColumn(name="fiveteen_id", referencedColumnName="id")}' .
					')'
			), $this->getPropertyOfType('Fiveteen', 'sixteens')->getAnnotations());
	}



	public function testManyToManyBidirectional()
	{
		$this->assertFalse($this->getPropertyOfType('Ten', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Ten', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Ten', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Ten', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Eleven', 'name')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Eleven', 'name')->isManyToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Eleven', 'name')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var string',
				'@Column(type="string")'
			), $this->getPropertyOfType('Eleven', 'name')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Eleven', 'tens')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Eleven', 'tens')->isManyToManyUnidirectional());
		$this->assertTrue($this->getPropertyOfType('Eleven', 'tens')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Collection',
				'@ManyToMany(targetEntity="Ten", inversedBy="elevens")',
				'@JoinTable(name="tens_elevens)'
			), $this->getPropertyOfType('Eleven', 'tens')->getAnnotations());

		$this->assertFalse($this->getPropertyOfType('Ten', 'elevens')->isOneToManyUnidirectional());
		$this->assertFalse($this->getPropertyOfType('Ten', 'elevens')->isManyToManyUnidirectional());
		$this->assertTrue($this->getPropertyOfType('Ten', 'elevens')->isManyToManyBidirectional());

		$this->assertSame(array(
				'@var Collection',
				'@ManyToMany(targetEntity="Eleven", mappedBy="tens")',
			), $this->getPropertyOfType('Ten', 'elevens')->getAnnotations());

	}

}