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

}