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
use Nette\Templating\Template;



/**
 * @author Filip Procházka
 */
class EntityClassWritter extends Nette\Object
{

	/** @var Template */
	private $template;



	/**
	 * @param Template $templatePrototype
	 */
	public function __construct(Template $templatePrototype)
	{
		$this->template = $templatePrototype;
	}



	/**
	 * @param BaseType $type
	 * @return string
	 */
	public function write(BaseType $type)
	{
		if ($type instanceof ClassType) {
			$this->template->setFile(__DIR__ . '/templates/Entity.latte');

		} elseif ($type instanceof InterfaceType) {
			$this->template->setFile(__DIR__ . '/templates/Interface.latte');

		} else {
			throw new Nette\NotImplementedException("Unknown type");
		}

		$this->template->type = $type;

		// trim trailing spaces
		$code = (string)$this->template;
		$lines = Nette\Utils\Strings::split($code, '~[\n\r]~');
		$lines = array_map(callback('rtrim'), $lines);
		return implode("\n", $lines);
	}

}