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

	/** @var array */
	private $errors = array();

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
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}



	/**
	 * @return object
	 */
	private function read()
	{
		if (!$this->packages || !$this->types) {
			$this->parseXml();
			$mapper = function ($object) { return $object->getFullName(); };

			if ($this->packages) {
				$this->packages = array_combine(array_map($mapper, $this->packages), $this->packages);
				ksort($this->packages);
			}

			if ($this->types) {
				$this->types = array_combine(array_map($mapper, $this->types), $this->types);
				ksort($this->types);

				foreach ($this->types as $type) {
					$this->parseTypeAttributes($type);
				}

				foreach ($this->types as $type) {
					$this->finalizeConnections($type);
				}
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
			//throw new Nette\NotImplementedException("Unknown object");
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
				if ($types[0] instanceof InterfaceType) {
					$types[0]->extends[] = $types[1];

				} else {
					$types[0]->extends = $types[1];
				}

				break;
			case 'triangle-dotted': // implements
				if ($types[0] instanceof ClassType) {
					$types[0]->implements[] = $types[1];

				} else {
					$this->errors[] = 'Interface ' . $types[0]->getFullName() . ' cannot implement ' . $types[1]->getFullName();
				}

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
			foreach ($type->properties as $property) {
				$property->parentType = $type;
				$property->type = $this->resolveNearestType($type, $property->type);
				$property->subtype = $this->resolveNearestType($type, $property->subtype);
				$property->defaultValue = $this->resolveNearestType($type, $property->defaultValue);
			}
		}

		if (is_string($type->methods)) {
			$type->methods = $this->methodsLexer->parse($type->methods);
			foreach ($type->methods as $method) {
				$method->parentType = $type;
				$method->returns = $this->resolveNearestType($type, $method->returns);
				$method->returnsSubtype = $this->resolveNearestType($type, $method->returnsSubtype);
				foreach ($method->args as $name => &$argType) {
					$argType = $this->resolveNearestType($type, $argType);
				}
			}
		}
	}



	/**
	 * @param BaseType $type
	 * @param string $typeName
	 * @return string|BaseType
	 */
	private function resolveNearestType(BaseType $type, $typeName)
	{
		if (!$typeName) {
			return $typeName;
		}

		$package = $type->package;
		while ($package) {
			$match = $this->checkTypesNames($package->types, $typeName);
			if ($match) {
				return $match;
			}

			$package = $package->package;
		}

		return $this->checkTypesNames($this->types, $typeName) ?: $typeName;
	}



	/**
	 * @param array $types
	 * @param string $typeName
	 * @return object|NULL
	 */
	private function checkTypesNames(array $types, $typeName)
	{
		foreach ($types as $sibling) {
			if ($typeName === $sibling->name) {
				return $sibling;

			} elseif ($typeName === $sibling->getFullName()) {
				return $sibling;
			}

			// TODO: think about more types of relations
		}

		return NULL;
	}



	/**
	 * @param BaseType $type
	 */
	private function finalizeConnections(BaseType $type)
	{
		if (!$type instanceof ClassType) {
			return;
		}

		foreach ($type->relations as $relationName => $relations) {
			foreach ($relations as $relatedType) {
				$this->finalizeConnection($type, $relatedType, $relationName);
			}
		}
	}



	/**
	 * @param ClassType $type
	 * @param ClassType $relatedType
	 * @param string $relationName
	 */
	private function finalizeConnection(ClassType $type, ClassType $relatedType, $relationName)
	{
		$left = $this->resolveTypePropertiesRelations($type, $relatedType);
		$right = $this->resolveTypePropertiesRelations($relatedType, $type, is_array($left) ? $left : array($left));
		$relations = $this->resolveRelationsIntersection($type, $relatedType);

		if (!$left && !$right) {
			$this->errors[] = "Relation of " . $type->getFullName() . " -> " . $relatedType->getFullName() . " can't be resolved";
			return;
		}

//		echo "-------------\n";
//		var_dump($type->getFullName() . ' -> ' . $relatedType->getFullName());
//		var_dump($relations);

		$leftProperty = is_array($left) ? reset($left) : $left;
		$rightProperty = is_array($right) ? reset($right) : $right;

		if ($left) {
			$leftProperty->relationType = is_object($left) ? Property::RELATION_HAS_ONE : Property::RELATION_HAS_MANY;
			$leftProperty->relation = $relatedType;

			if ($right) {
				if ($leftProperty->parentType === $rightProperty->parentType) { // self referencing
					$rightProperty->relationType = is_object($right) ? Property::RELATION_HAS_ONE : Property::RELATION_HAS_MANY;
					$rightProperty->relation = $type;
				}

			} elseif ($relations[$relationName] <= 1) { // Unidirectional
				// Many-To-One
				if ($leftProperty->relationType === Property::RELATION_HAS_ONE && $relationName === 'aggregation') {
					$leftProperty->otherSideRelationType = Property::RELATION_HAS_MANY;
				}

				// One-To-Many
				if ($leftProperty->relationType === Property::RELATION_HAS_MANY && $relationName === 'aggregation') {
					$leftProperty->otherSideRelationType = Property::RELATION_HAS_ONE;
				}
			}
		}
	}



	/**
	 * @param ClassType $type
	 * @param ClassType $relatedType
	 * @return array
	 */
	private function resolveRelationsIntersection(ClassType $type, ClassType $relatedType)
	{
		$relationsCounts = array(
			'composition' => 0,
			'aggregation' => 0,
		);

		foreach ($type->relations as $relationName => $relations) {
			foreach ($relations as $otherSide) {
				if ($relatedType === $otherSide) {
					$relationsCounts[$relationName] += 1;
				}
			}
		}

		return $relationsCounts;
	}



	/**
	 * @param ClassType $type
	 * @param ClassType $relatedType
	 */
	private function resolveTypePropertiesRelations(ClassType $type, ClassType $relatedType, array $ignore = array())
	{
		do {
			foreach ($type->properties as $property) {
				if ($this->resolveRelationType($property, $relatedType, $relation)) {
					$relatedProperty = is_array($relation) ? reset($relation) : $relation;
					if (!in_array($relatedProperty, $ignore, TRUE)) {
						return $relation;
					}
				}
			}

		} while($type = $type->extends);

		return NULL;
	}



	/**
	 * @param Property $property
	 * @param ClassType $type
	 * @param NULL $relation
	 * @return boolean
	 */
	private function resolveRelationType(Property $property, ClassType $type, &$relation)
	{
		if ($property->type === $type) {
			$relation = $property;

		} elseif ($property->type === 'Collection' && $property->subtype === $type) {
			$relation = array($property);
		}

		return (bool)$relation;
	}

}