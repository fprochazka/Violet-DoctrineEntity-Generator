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
class Method extends Nette\Object
{

	const VISIBILITY_PUBLIC = 'public';
	const VISIBILITY_PRIVATE = 'private';
	const VISIBILITY_PROTECTED = 'protected';


	/** @var string */
	public $name;

	/** @var string */
	public $visibility = self::VISIBILITY_PUBLIC;

	/** @var array */
	public $args = array();

	/** @var string */
	public $returns = 'string';

	/** @var string */
	public $returnsSubtype;

}