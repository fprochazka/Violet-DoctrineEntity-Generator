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
class Property extends Nette\Object
{

	const VISIBILITY_PUBLIC = 'public';
	const VISIBILITY_PRIVATE = 'private';
	const VISIBILITY_PROTECTED = 'protected';

	const RELATION_ONE_TO_ONE = 'OneToOne';
	const RELATION_ONE_TO_MANY = 'OneToMany';
	const RELATION_MANY_TO_MANY = 'ManyToMany';
	const RELATION_MANY_TO_One = 'ManyToOne';


	/** @var string */
	public $name;

	/** @var string */
	public $visibility = self::VISIBILITY_PRIVATE;

	/** @var string */
	public $type = 'string';

	/** @var string */
	public $subtype;

	/** @var string */
	public $defaultValue;

	/** @var Property */
	public $relation;

	/** @var string */
	public $relationType;

}