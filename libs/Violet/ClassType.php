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



/**
 * @author Filip Procházka
 */
class ClassType extends BaseType
{

	/** @var array */
	public $properties = array();

	/** @var array */
	public $methods = array();

	/** @var string */
	public $extends;

	/** @var array */
	public $implements = array();

	/** @var array */
	public $relations = array();



	/**
	 * @param BaseType $type
	 */
	public function addComposedOf(ClassType $type)
	{
		$this->relations['composition'][] = $type;
		if ($type !== $this) {
			$type->relations['composition'][] = $this;
		}
	}



	/**
	 * @param BaseType $type
	 */
	public function addAggregateOf(ClassType $type)
	{
		$this->relations['aggregation'][] = $type;
		if ($type !== $this) {
			$type->relations['aggregation'][] = $this;
		}
	}



	/**
	 * @return bool
	 */
	public function hasCollections()
	{
		foreach ($this->properties as $property) {
			if ($property->type == 'Collection') {
				return TRUE;
			}
		}

		return FALSE;
	}



	/**
	 * @return array
	 */
	public function getUsedRootPackages()
	{
		$packages = array(
			is_object($this->extends) ? $this->extends->getRootPackage() : NULL
		);

		foreach ($this->implements as $interface) {
			$packages[] = $interface->getRootPackage();
		}

		foreach ($this->properties as $property) {
			$packages[] = is_object($property->type) ? $property->type->getRootPackage() : NULL;
			$packages[] = is_object($property->subtype) ? $property->subtype->getRootPackage() : NULL;
			$packages[] = is_object($property->defaultValue) ? $property->defaultValue->getRootPackage() : NULL;
			$packages[] = is_object($property->relation) ? $property->relation->getRootPackage() : NULL;
		}

		foreach ($this->methods as $method) {
			$packages[] = is_object($method->returns) ? $method->returns->getRootPackage() : NULL;
			$packages[] = is_object($method->returnsSubtype) ? $method->returnsSubtype->getRootPackage() : NULL;
			foreach ($method->args as $argName => $argType) {
				$packages[] = is_object($argType) ? $argType->getRootPackage() : NULL;
			}
		}

		$packages = array_unique(array_filter($packages));
		sort($packages);
		return $packages;
	}



	/**
	 * @return type
	 */
	public function getAllMethods()
	{
		$methods = $this->getMethodsRequiredByInterface();
		foreach ($this->methods as $method) { // implementation
			$methods[$method->name] = $method;
		}

		if ($this->extends) { // parent interfaces
			$methods += $this->extends->getMethodsRequiredByInterface();
		}

		return $methods;
	}



	/**
	 * @return array
	 */
	public function getMethodsRequiredByInterface()
	{
		$methods = array();

		foreach ($this->implements as $interface) {
			foreach ($interface->getAllMethods() as $method) {
				if (!$this->hasMethod($method->name)) {
					$methods[$method->name] = $method;
				}
			}
		}

		return $methods;
	}



	/**
	 * @param string $name
	 * @return boolean
	 */
	public function hasMethod($name)
	{
		foreach ($this->methods as $method) {
			if ($method->name === $name) {
				return TRUE;
			}
		}

		return FALSE;
	}

}