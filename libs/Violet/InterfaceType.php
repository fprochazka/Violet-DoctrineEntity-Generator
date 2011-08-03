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
class InterfaceType extends BaseType
{

	/** @var array */
	public $extends = array();

	/** @var array */
	public $methods = array();



	/**
	 * @return array
	 */
	public function getUsedRootPackages()
	{
		foreach ($this->extends as $interface) {
			$packages[] = $interface->getRootPackage();
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

}