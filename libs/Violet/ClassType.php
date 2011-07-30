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



	public function addComposedOf(BaseType $type)
	{
		$this->relations['composition'][] = $type;
		$type->relations['composition'][] = $this;
	}


	public function addAggregateOf(BaseType $type)
	{
		$this->relations['aggregation'][] = $type;
		$type->relations['aggregation'][] = $this;
	}

}