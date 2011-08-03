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
abstract class BaseType extends Nette\Object
{

	/** @var string */
	public $name;

	/** @var Package */
	public $package;



	/**
	 * @return string
	 */
	public function getFullName()
	{
		return ($this->package ? $this->package->getFullName() . '\\' : NULL) . $this->name;
	}



	/**
	 * @return string
	 */
	public function getWebalizedName()
	{
		return str_replace('-', '', Nette\Utils\Strings::webalize($this->name));
	}



	/**
	 * @param BaseType $type
	 * @return string
	 */
	public function getRelativeName($type)
	{
		if (!$type instanceof BaseType) {
			return $type;
		}

		return $type->package !== $this->package
			? $type->getFullName()
			: $type->name;
	}



	/**
	 * @return array
	 */
	public function getUsedRootPackages()
	{
		return array();
	}



	/**
	 * @return Package
	 */
	public function getRootPackage()
	{
		$package = $this->package;
		while ($package && $package->package) {
			// we need to go deeper!
			$package = $package->package;
		}

		return $package ? $package->getFullName() : NULL;
	}

}