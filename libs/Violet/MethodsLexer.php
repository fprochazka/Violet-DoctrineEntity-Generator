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
class MethodsLexer extends Nette\Utils\Tokenizer
{

	/** @var array */
	public $methods = array();



	public function __construct()
	{
		parent::__construct(array(
			'visibility' => '[#+-]',
			'identifier' => '[_a-zA-Z\x7F-\xFF][_a-zA-Z0-9\x7F-\xFF\\\\-]*',
			'args' => '[()]',
			'type' => '\:',
			'default' => '\=',
			'subtype' => '[<>]',
			'whitespace' => '[\t ]+',
			'separator' => ',',
			'next' => '[\r\n]+',
		), 'im');
	}



	/**
	 * @param string $methodsString
	 * @return array
	 */
	public function parse($methodsString)
	{
		$this->methods = array();
		$this->position = 0;
		$this->tokenize($methodsString);

		while ($this->hasNext()) {
			$method = $visibility = NULL;
			while ($token = $this->fetchToken()) {
				if ($token['type'] === 'visibility') {
					$visibility = strtr($token['value'], array(
						'-' => Method::VISIBILITY_PRIVATE,
						'#' => Method::VISIBILITY_PROTECTED,
						'+' => Method::VISIBILITY_PUBLIC,
					));

				} elseif ($token['type'] === 'identifier' && !$method) {
					$this->methods[] = $method = new Method;
					$method->name = $token['value'];

				} elseif ($token['value'] === '(' && !$this->isNext('args')) {
					$typeOrName = NULL;
					while ($token = $this->fetchToken()) {
						if ($token['type'] === 'identifier') {
							if ($typeOrName) { // type
								$method->args[$token['value']] = $typeOrName;
								$typeOrName = NULL;

							} else {
								$typeOrName = $token['value'];
							}
						}

						if ($this->isNext('args')) {
							if ($typeOrName) {
								$method->args[$typeOrName] = 'mixed';
								$typeOrName = NULL;
							}

							break;
						}
					}

				} elseif ($token['type'] === 'type') {
					$this->fetchUntil('identifier');
					$method->returns = $this->fetch();

				} elseif ($token['type'] === 'subtype') {
					$method->returnsSubtype = trim($this->fetchUntil('subtype'), '<>');
					$this->fetch(); // skip the >
				}

				if ($this->isCurrent('next')) {
					break;
				}
			}

			if ($visibility) {
				$method->visibility = $visibility;
			}
		}

		return $this->methods;
	}

}