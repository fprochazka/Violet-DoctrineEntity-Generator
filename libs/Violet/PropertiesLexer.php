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
class PropertiesLexer extends Nette\Utils\Tokenizer
{

	/** @var array */
	public $properties = array();



	public function __construct()
	{
		parent::__construct(array(
			'visibility' => '[#+-]',
			'identifier' => '[_a-zA-Z0-9\x7F-\xFF\\\\-]*',
			'args' => '[()]',
			'type' => '\:',
			'default' => '\=',
			'subtype' => '[<>]',
			'whitespace' => '[\t ]+',
			'next' => '[\r\n]+',
		), 'im');
	}



	/**
	 * @param string $propertiesString
	 * @return array
	 */
	public function parse($propertiesString)
	{
		$this->properties = array();
		$this->position = 0;
		$this->tokenize($propertiesString);

		while ($this->hasNext()) {
			$property = $visibility = NULL;
			while ($token = $this->fetchToken()) {
				if ($token['type'] === 'visibility') {
					$visibility = strtr($token['value'], array(
						'-' => Property::VISIBILITY_PRIVATE,
						'#' => Property::VISIBILITY_PROTECTED,
						'+' => Property::VISIBILITY_PUBLIC,
					));

				} elseif ($token['type'] === 'identifier' && !$property) {
					$this->properties[] = $property = new Property;
					$property->name = $token['value'];

				} elseif ($token['type'] === 'type') {
					$this->fetchUntil('identifier');
					$property->type = $this->fetch();

				} elseif ($token['type'] === 'default') {
					$this->fetchUntil('identifier');
					$property->defaultValue = $this->fetch();

				} elseif ($token['type'] === 'subtype') {
					$property->subtype = trim($this->fetchUntil('subtype'), '<>');
					$this->fetch(); // skip the >
				}

				if ($this->isCurrent('next')) {
					break;
				}
			}

			if ($visibility) {
				$property->visibility = $visibility;
			}
		}

		return $this->properties;
	}

}