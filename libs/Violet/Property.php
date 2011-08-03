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

	const RELATION_HAS_ONE = 'HasOne';
	const RELATION_HAS_MANY = 'HasMany';


	/** @var string */
	public $name;

	/** @var BaseType */
	public $parentType;

	/** @var string */
	public $visibility = self::VISIBILITY_PRIVATE;

	/** @var string */
	public $type = 'string';

	/** @var string */
	public $subtype;

	/** @var string */
	public $defaultValue;

	/** @var ClassType */
	public $relation;

	/** @var string */
	public $relationType;

	/** @var string */
	public $bidirectional;

	/** @var string */
	public $otherSideRelationType;

	/** @var Property */
	private $otherSideOfRelation = FALSE; // FALSE === uninitialized



	/**
	 * @return bool
	 */
	public function isCollection()
	{
		return $this->relation && $this->relationType === self::RELATION_HAS_MANY;
	}



	/**
	 * @return array
	 */
	public function getAnnotations()
	{
		return array_values(array_filter(array(
				$this->createVarAnnotation(),
				$this->createColumnAnnotation(),
				$this->createRelationAnnotation(),
				$this->createJoinColumnAnnotation(),
				$this->createJoinTableAnnotation()
			)));
	}



	/**
	 * @return boolean
	 */
	public function isMappingRelation()
	{
		if ($this->isUnidirectional()) {
			return TRUE;
		}

		$relationName = $this->getDoctrineRelationName();
		if ($relationName === 'ManyToOne') {
			return TRUE;

		} elseif ($relationName === 'OneToMany') {
			return FALSE;
		}

		$otherSide = $this->getOtherSideOfRelation();
		$names = array(
				$this->parentType->getFullName() => $this,
				$otherSide->parentType->getFullName() => $otherSide
			);

		ksort($names);
		return current($names) === $this;
	}



	/**
	 * @return string
	 */
	private function createVarAnnotation()
	{
		return '@var ' . $this->parentType->getRelativeName($this->type);
	}



	/**
	 * @return string
	 */
	private function createColumnAnnotation()
	{
		if ($this->relation) {
			return NULL;
		}

		return '@Column(type="' . ($this->type === 'Collection'
					? $this->parentType->getRelativeName($this->subtype)
					: $this->parentType->getRelativeName($this->type)
				) .'")';
	}



	/**
	 * @return string
	 */
	private function getDoctrineRelationName()
	{
		if ($this->isUnidirectional()) {
			if ($this->relationType === self::RELATION_HAS_ONE) {
				return $this->otherSideRelationType === self::RELATION_HAS_MANY ? 'ManyToOne' : 'OneToOne';

			} else { // OneToMany unidirectional
				return 'ManyToMany';
			}

		} else {
			$otherSide = $this->getOtherSideOfRelation();
			if ($this->relationType === self::RELATION_HAS_ONE) {
				return $otherSide->relationType === self::RELATION_HAS_ONE ? 'OneToOne' : 'ManyToOne';

			} else {
				return $otherSide->relationType === self::RELATION_HAS_ONE ? 'OneToMany' : 'ManyToMany';
			}
		}
	}



	/**
	 * @return string
	 */
	private function createRelationAnnotation()
	{
		if (!$this->relation) {
			return NULL;
		}

		$relationName = $this->getDoctrineRelationName();
		$s = '@' . $relationName . '(targetEntity="' . $this->parentType->getRelativeName($this->relation) . '"';

		$otherSide = $this->getOtherSideOfRelation();
		if ($otherSide) {
			$s .= ', ' . ($otherSide->isMappingRelation() ? 'mappedBy' : 'inversedBy') . '="' . $otherSide->name . '"';
		}

		return $s . ')';
	}



	/**
	 * @return string
	 */
	private function createJoinColumnAnnotation()
	{
		if (!$this->hasJoinColumnAnnotation()) {
			return NULL;
		}

		return $this->getThisJoinColumnAnnotation();
	}



	/**
	 * @return string
	 */
	private function getThisJoinColumnAnnotation()
	{
		return '@JoinColumn(' .
				'name="' . \Inflector::singularize($this->name) . '_id", ' .
				'referencedColumnName="' . $this->getReferencedColumnName() . '"' .
			')';
	}



	/**
	 * @return string
	 */
	private function createJoinTableAnnotation()
	{
		if (!$this->relation || !$this->isMappingRelation()) {
			return NULL;
		}

		// joinColumns je strana, kde by bylo mappedBy
		if ($this->isOneToManyUnidirectional()) {
			return '@JoinTable(' .
					'name="' . $this->getManyToManyTableName() . '", ' .
					'joinColumns={' . $this->getThisJoinColumnAnnotation() . ', unique=TRUE}, ' .
					'inverseJoinColumns={' . $this->createOtherSideJoinColumnAnnotation() . '}' .
				')';

		} elseif ($this->isManyToManyUnidirectional()) {
			return '@JoinTable(' .
					'name="' . $this->getManyToManyTableName() . '", ' .
					'joinColumns={' . $this->getThisJoinColumnAnnotation() . '}, ' .
					'inverseJoinColumns={' . $this->createOtherSideJoinColumnAnnotation() . '}' .
				')';

		} elseif ($this->isManyToManyBidirectional()) {
			return '@JoinTable(name="' . $this->name . '_' . $this->getOtherSideOfRelation()->name . ')';
		}

		return NULL;
	}



	/**
	 * @return string
	 */
	private function getManyToManyTableName()
	{
		return \Inflector::pluralize($this->parentType->webalizedName) . '_' . \Inflector::pluralize($this->relation->webalizedName);
	}



	/**
	 * @return string
	 */
	private function createOtherSideJoinColumnAnnotation()
	{
		$otherSide = $this->getOtherSideOfRelation();
		if (!$otherSide) {
			return '@JoinColumn(' .
				'name="' . \Inflector::singularize($this->parentType->webalizedName) . '_id", ' .
				'referencedColumnName="' . 'id' . '"' .
			')';
		}

		return '@JoinColumn(' .
				'name="' . \Inflector::singularize($otherSide->name) . '_id", ' .
				'referencedColumnName="' . $otherSide->getReferencedColumnName() . '"' .
			')';
	}



	/**
	 * @return string
	 */
	private function getReferencedColumnName()
	{
		return 'id';
		//$otherSide = $this->otherSideOfRelation;
		//return $otherSide ? $otherSide->name : 'id';
	}



	/**
	 * @return bool
	 */
	private function hasJoinColumnAnnotation()
	{
		return $this->relation
			&& $this->isMappingRelation()
			&& $this->getDoctrineRelationName() !== 'ManyToMany';
	}



	/**
	 * @return bool
	 */
	public function isUnidirectional()
	{
		return !$this->getOtherSideOfRelation();
	}



	/**
	 * @return bool
	 */
	public function isOneToManyUnidirectional()
	{
		return $this->isCollection()
			&& $this->otherSideRelationType === self::RELATION_HAS_ONE
			&& $this->isUnidirectional()
			&& $this->isMappingRelation();
	}



	/**
	 * @return boolean
	 */
	public function isManyToManyUnidirectional()
	{
		return $this->isCollection()
			&& $this->otherSideRelationType === NULL
			&& $this->isUnidirectional();
	}



	/**
	 * @return boolean
	 */
	public function isManyToManyBidirectional()
	{
		return $this->isCollection()
			&& !$this->isUnidirectional()
			&& $this->getOtherSideOfRelation()->isCollection();
	}



	/**
	 * @return Property
	 */
	public function getOtherSideOfRelation()
	{
		if ($this->otherSideOfRelation) {
			return $this->otherSideOfRelation;
		}

		if (!$this->relation) {
			return $this->otherSideOfRelation = NULL;
		}

		$type = $this->relation;
		do {
			foreach ($type->properties as $property) {
				if ($this->resolveRelationType($property, $relation)) {
					$relation = is_array($relation) ? reset($relation) : $relation;
					if ($relation !== $this && $this->parentType === $relation->relation) {
						return $this->otherSideOfRelation = $relation;
					}
				}
			}

		} while($type = $type->extends);

		return $this->otherSideOfRelation = NULL;
	}



	/**
	 * @param Property $property
	 * @param NULL $relation
	 * @return boolean
	 */
	private function resolveRelationType(Property $property, &$relation)
	{
		if ($property->type === $this->parentType) {
			$relation = $property;

		} elseif ($property->subtype === $this->parentType) {
			$relation = array($property);
		}

		return (bool)$relation;
	}

}