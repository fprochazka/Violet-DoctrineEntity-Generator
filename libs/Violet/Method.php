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

	/** @var BaseType */
	public $parentType;

	/** @var string */
	public $visibility = self::VISIBILITY_PUBLIC;

	/** @var array */
	public $args = array();

	/** @var string */
	public $returns = 'string';

	/** @var string */
	public $returnsSubtype;



	/**
	 * @param boolean $phpDoc
	 * @return array
	 */
	public function getFormatedArgs($phpDoc = FALSE)
	{
		$args = array();
		foreach ($this->args as $name => $type) {
			if (is_object($type)) {
				$args[$name] = $this->parentType->getRelativeName($this->returns);

			} else {
				$args[$name] = $phpDoc ? $type : NULL;
			}
		}

		return $args;
	}



	/**
	 * @return string
	 */
	public function getReturnType()
	{
		if ($this->returns === 'Collection') {
			if (is_object($this->returnsSubtype)) {
				return $this->parentType->getRelativeName($this->returnsSubtype) . '[]';
			}

		} elseif (is_object($this->returns)) {
			return $this->parentType->getRelativeName($this->returns);
		}

		return $this->returns;
	}

}