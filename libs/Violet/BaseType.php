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

}