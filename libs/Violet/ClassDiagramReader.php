<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008, 2011 Filip Procházka (filip.prochazka@kdyby.org)
 *
 * @license http://www.kdyby.org/license
 */

namespace Kdyby\Violet;

use Kdyby;
use Nette;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka
 */
class ClassDiagramReader extends Nette\Object
{

	/** @var string */
	private $contents;

	/** @var array */
	private $packages = array();

	/** @var array */
	private $types = array();

	/** @var PropertiesLexer */
	private $propertiesLexer;

	/** @var MethodsLexer */
	private $methodsLexer;



	/**
	 * @param string $contents
	 */
	public function __construct($contents)
	{
		$this->contents = $contents;
		$this->propertiesLexer = new PropertiesLexer();
		$this->methodsLexer = new MethodsLexer();
	}



	/**
	 * @return array
	 */
	public function getPackages()
	{
		return $this->read()->packages;
	}



	/**
	 * @return array
	 */
	public function getTypes()
	{
		return $this->read()->types;
	}



	/**
	 * @return object
	 */
	private function read()
	{
		if (!$this->packages || !$this->types) {
			$this->parseXml();
			$mapper = function ($object) { return $object->getFullName(); };

			$this->packages = array_combine(array_map($mapper, $this->packages), $this->packages);
			ksort($this->packages);

			$this->types = array_combine(array_map($mapper, $this->types), $this->types);
			ksort($this->types);

			foreach ($this->types as $type) {
				$this->parseTypeAttributes($type);
			}

			foreach ($this->types as $type) {
				$this->finalizeConnections($type);
			}
		}

		return (object)array(
			'packages' => $this->packages,
			'types' => $this->types,
		);
	}



	/**
	 * @throws Nette\InvalidArgumentException
	 */
	private function parseXml()
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->resolveExternals = FALSE;
		$dom->validateOnParse = FALSE;
		$dom->preserveWhiteSpace = FALSE;
		if (!$dom->loadXML($this->contents)) {
			throw new Nette\InvalidArgumentException("Invalid XML");
		}

		if ($dom->firstChild->nodeName !== 'java') {
			throw new Nette\InvalidArgumentException("Root node must be 'java'");
		}

		foreach ($dom->firstChild->childNodes as $graph) {
			if ($graph->nodeName === 'object') {
				$this->createObject($graph);
				return;
			}

			throw new Nette\InvalidArgumentException("Serialized ClassDiagramGraph not found.");
		}
	}



	/**
	 * @param \DOMElement $objectElement
	 * @throws Nette\InvalidArgumentException
	 * @return Package|BaseType
	 */
	private function createObject(\DOMElement $objectElement)
	{
		if ($objectElement->nodeName !== 'object') {
			throw new Nette\InvalidArgumentException("Given element is not a serialized object, " . $objectElement->nodeName . " given");
		}

		$class = $objectElement->getAttribute('class');
		$type = substr($class, strrpos($class, '.') + 1);

		if ($type === 'ClassDiagramGraph') {
			foreach ($objectElement->childNodes as $node) {
				if ($node->getAttribute('method') === 'addNode') {
					$this->createObject($node->firstChild);

				} elseif ($node->getAttribute('method') === 'connect') {
					$this->createConnection($node);
				}
			}

		} elseif ($type === 'PackageNode') {
			return $this->createPackage($objectElement);

		} elseif ($type === 'InterfaceNode') {
			return $this->createInterface($objectElement);

		} elseif ($type === 'ClassNode') {
			return $this->createClass($objectElement);

		} elseif ($idref = $objectElement->getAttribute('idref')) {
			if (isset($this->packages[$idref])) {
				return $this->packages[$idref];

			} elseif (isset($this->types[$idref])) {
				return $this->types[$idref];
			}

		} else {
			throw new Nette\NotImplementedException("Unknown object");
		}
	}



	/**
	 * NONE - note
	 * TRIANGLE - inherits/implements
	 * BLACK_TRIANGLE -
	 * V - association
	 * HALF_V - depends on
	 * DIAMOND - aggregate of
	 * BLACK_DIAMOND - composed of
	 *
	 * @param \DOMElement $connectElement
	 */
	private function createConnection(\DOMElement $connectElement)
	{
		$attrs = (object)array(
			"startArrowHead" => NULL,
			"startLabel" => NULL,
			"middleLabel" => NULL,
			"endLabel" => NULL,
			"endArrowHead" => NULL,
			"bentStyle" => NULL,
			"lineStyle" => NULL,
		);
		foreach ($connectElement->firstChild->childNodes as $node) {
			$attrs->{$node->getAttribute('property')} = strtolower($node->firstChild->getAttribute('field'));
		}

		$types = array();
		foreach ($connectElement->childNodes as $node) {
			if ($node->getAttribute('idref')) {
				$types[] = $this->types[$node->getAttribute('idref')];
			}
		}

		if ($attrs->startArrowHead) {
			array_reverse($types);
		}

		switch ($attrs->startArrowHead . $attrs->endArrowHead . '-' . $attrs->lineStyle) {
			case '-dotted': // note
				break;
			case 'triangle-': // inherits
				$types[0]->extends = $types[1];
				break;
			case 'triangle-dotted': // implements
				$types[0]->implements[] = $types[1];
				break;
			case 'black_triangle-': // BLACK_TRIANGLE -
				break;
			case 'v-': // association
				break;
			case 'v-dotted': // depends on
				break;
			case 'half_v-': //
				break;
			case 'diamond-': // aggregate of
				$types[0]->addAggregateOf($types[1]);
				break;
			case 'black_diamond-': // composed of
				$types[0]->addComposedOf($types[1]);
				break;
		}
	}



	/**
	 * @param \DOMElement $packageElement
	 * @throws Nette\InvalidArgumentException
	 * @return Package
	 */
	private function createPackage(\DOMElement $packageElement)
	{
		$id = $packageElement->getAttribute('id') ?: count($this->packages);
		if (isset($this->packages[$id])) {
			$package = $this->packages[$id];

		} else {
			$this->packages[$id] = $package = new Package();
		}

		foreach ($packageElement->childNodes as $node) {
			if ($node->getAttribute('method') === 'addChild') {
				$object = $this->createObject($node->firstChild);
				if ($object instanceof Package) {
					$package->packages[$object->name] = $object;

				} elseif ($object instanceof BaseType) {
					$package->types[$object->name] = $object;

				} else {
					throw new Nette\InvalidArgumentException("Unknown child of package $id " . (is_object($object) ? get_class($class) : gettype($class)));
				}

				$object->package = $package;

			} elseif ($node->getAttribute('property')) {
				$property = $this->readProperty($node);
				if ($property->name === 'name') {
					$property->value = $this->sanitizeTypeName($property->value);
				}

				$package->{$property->name} = $property->value;
			}
		}

		return $package;
	}



	/**
	 * @param \DOMElement $interfaceElement
	 * @return InterfaceType
	 */
	private function createInterface(\DOMElement $interfaceElement)
	{
		$id = $interfaceElement->getAttribute('id') ?: count($this->packages);
		if (isset($this->types[$id])) {
			$interface = $this->types[$id];

		} else {
			$this->types[$id] = $interface = new InterfaceType();
		}

		foreach ($interfaceElement->childNodes as $node) {
			if ($node->getAttribute('property')) {
				$property = $this->readProperty($node);
				if ($property->name === 'name') {
					$property->value = $this->sanitizeTypeName($property->value);
				}

				$interface->{$property->name} = $property->value;
			}
		}

		return $interface;
	}



	/**
	 * @param \DOMElement $classElement
	 * @return ClassType
	 */
	private function createClass(\DOMElement $classElement)
	{
		$id = $classElement->getAttribute('id') ?: count($this->packages);
		if (isset($this->types[$id])) {
			$class = $this->types[$id];

		} else {
			$this->types[$id] = $class = new ClassType;
		}

		foreach ($classElement->childNodes as $node) {
			if ($node->getAttribute('property')) {
				$property = $this->readProperty($node);
				$property->name = str_replace('attributes', 'properties', $property->name);
				if ($property->name === 'name') {
					$property->value = $this->sanitizeTypeName($property->value);
				}

				$class->{$property->name} = $property->value;
			}
		}

		return $class;
	}



	/**
	 * Input:
	 *
	 * /--code
	 * <void property="name">
	 *	<void property="text">
	 *		<string>Nette\Permission</string>
	 *	</void>
	 * </void>
	 * \--
	 *
	 * /--code
	 * <void property="name">
	 *	<string>Security</string>
	 * </void>
	 * \--
	 *
	 * @param \DOMElement $propertyElement
	 * @return object
	 */
	private function readProperty(\DOMElement $propertyElement)
	{
		$textElement = $propertyElement->firstChild;

		$text = $textElement instanceof \DOMText
			? (string)$textElement->nodeValue
			: $this->readProperty($textElement)->value;

		return (object)array('name' => $propertyElement->getAttribute('property'), 'value' => $text);
	}



	/**
	 * @param string $type
	 * @return string
	 */
	private function sanitizeTypeName($type)
	{
		return str_replace('-', '', Strings::webalize($type, '\\', FALSE));
	}



	/**
	 * @param BaseType $type
	 */
	private function parseTypeAttributes(BaseType $type)
	{
		if ($type instanceof ClassType && is_string($type->properties)) {
			$type->properties = $this->propertiesLexer->parse($type->properties);
		}

		if (is_string($type->methods)) {
			$type->methods = $this->methodsLexer->parse($type->methods);
		}
	}



	/**
	 * @param BaseType $type
	 */
	private function finalizeConnections(BaseType $type)
	{
		if (!$type instanceof ClassType) {
			return;
		}


	}

}