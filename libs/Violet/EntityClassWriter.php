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
use Nette\Utils\PhpGenerator as Code;



/**
 * @author Filip Procházka
 */
class EntityClassWriter extends Nette\Object
{
	static $ormAnnotations = array(
		'@entity' => '%sEntity',
		'@mappedsuperclass' => '%sMappedSuperClass',
		'@inheritancetype' => '%sInheritanceType',
		'@discriminatorcolumn' => '%sDiscriminatorColumn',
		'@discriminatormap' => '%sDiscriminatorMap',
		'@discriminatorentry' => '%sDiscriminatorEntry',
		'@id' => '%sId',
		'@generatedvalue' => '%sGeneratedValue',
		'@version' => '%sVersion',
		'@joincolumn' => '%sJoinColumn',
		'@joincolumns' => '%sJoinColumns',
		'@column' => '%sColumn',
		'@onetoone' => '%sOneToOne',
		'@onetomany' => '%sOneToMany',
		'@manytoone' => '%sManyToOne',
		'@manytomany' => '%sManyToMany',
		'@elementcollection' => '%sElementCollection',
		'@table' => '%sTable',
		'@uniqueconstraint' => '%sUniqueConstraint',
		'@index' => '%sIndex',
		'@jointable' => '%sJoinTable',
		'@sequencegenerator' => '%sSequenceGenerator',
		'@changetrackingpolicy' => '%sChangeTrackingPolicy',
		'@orderby' => '%sOrderBy',
		'@namedqueries' => '%sNamedQueries',
		'@namedquery' => '%sNamedQuery',
		'@haslifecyclecallbacks' => '%sHasLifeCycleCallbacks',
		'@prepersist' => '%sPrePersist',
		'@postpersist' => '%sPostPersist',
		'@preupdate' => '%sPreUpdate',
		'@postupdate' => '%sPostUpdate',
		'@preremove' => '%sPreRemove',
		'@postremove' => '%sPostRemove',
		'@postload' => '%sPostLoad',
	);

	/** @var string */
	public $ormPrefix = '@';

	/** @var string */
	public $fluent = TRUE;



	/**
	 * @param \Kdyby\Violet\BaseType $meta
	 * @return string
	 */
	public function write(BaseType $meta)
	{
		$code = '<?' . "php\n\n";
		$type = new Code\ClassType($meta->name);

		if ($meta instanceof ClassType) {
			$this->buildEntity($type, $meta);
			$code .= $this->generateEntityNamespaces($meta);

		} elseif ($meta instanceof InterfaceType) {
			$this->buildInterface($type, $meta);
			$code .= $this->generateInterfaceNamespaces($meta);

		} else {
			throw new Nette\NotImplementedException("Unknown type");
		}

		return $code . (string)$type;
	}



	/**
	 * @param \Kdyby\Violet\ClassType $meta
	 * @return string
	 */
	protected function generateEntityNamespaces(ClassType $meta)
	{
		$code = array();

		if ($meta->package) {
			$code[] = 'namespace ' . $meta->package->getFullName() . ";\n";
		}

		foreach ($meta->getUsedRootPackages() as $package) {
			$code[] = 'use ' . $package . ';';
		}

		if ($meta->hasCollections()) {
			$code[] = 'use Doctrine\Common\Collections\Collection';
			$code[] = 'use Doctrine\Common\Collections\ArrayCollection';
		}

		return implode("\n", $code) . "\n\n\n";
	}



	/**
	 * @param \Kdyby\Violet\InterfaceType $meta
	 *
	 * @return string
	 */
	protected function generateInterfaceNamespaces(InterfaceType $meta)
	{
		$code = array();

		if ($meta->package) {
			$code[] = 'namespace ' . $meta->package->getFullName() . ";\n";
		}

		foreach ($meta->getUsedRootPackages() as $package) {
			$code[] = 'use ' . $package . ';';
		}

		return implode("\n", $code) . "\n\n\n";
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\ClassType $meta
	 */
	protected function buildEntity(Code\ClassType $type, ClassType $meta)
	{
		$extends = $meta->getRelativeName($meta->extends);
		if ($extends) {
			$type->extends[] = $extends;
		}

		$type->addDocument($this->prefixOrmAnnotation('@Entity()'));

		foreach ($meta->implements as $implements) {
			$type->implements[] = $meta->getRelativeName($implements);
		}

		foreach ($meta->properties as $property) {
			$prop = $type->addProperty($property->name);
			$prop->setVisibility($property->visibility);

			foreach ($property->getAnnotations() as $annotation) {
				$prop->addDocument($this->prefixOrmAnnotation($annotation));
			}
		}

		$constructor = $type->addMethod('__construct');
		if ($meta->hasCollections()) {
			foreach ($meta->properties as $property) {
				if (!$property->isCollection()) {
					continue;
				}

				$constructor->addBody('$this->? = new ArrayCollection();', array(
					$property->name
				));
			}
		}

		foreach ($meta->properties as $property) {
			$this->buildEntityPropertyAccessor($type, $property);
		}

		foreach ($meta->getAllMethods() as $method) {
			$this->buildEntityMethod($type, $method);
		}
	}



	/**
	 * @param string $annotation
	 * @return string
	 */
	protected function prefixOrmAnnotation($annotation)
	{
		foreach (self::$ormAnnotations as $find => $mask) {
			$annotation = str_ireplace($find, sprintf($mask, $this->ormPrefix), $annotation);
		}

		return $annotation;
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\Property $property
	 */
	protected function buildEntityPropertyAccessor(Code\ClassType $type, Property $property)
	{
		if ($property->isCollection()) {
			$this->buildEntityPropertyCollectionAddMethod($type, $property);
			$this->buildEntityPropertyCollectionRemoveMethod($type, $property);
			$this->buildEntityPropertyCollectionGetMethod($type, $property);

		} else {
			$this->buildEntityPropertySetMethod($type, $property);
			$this->buildEntityPropertyGetMethod($type, $property);
		}
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\Method $meta
	 */
	protected function buildEntityMethod(Code\ClassType $type, Method $meta)
	{
		$method = $type->addMethod($meta->name);
		$method->setVisibility($meta->visibility);

		foreach ($meta->getFormatedArgs() as $arg) {
			$method->addParameter($arg->name)
				->setTypeHint($arg->type);
		}

		foreach ($meta->getFormatedArgs() as $arg) {
			$method->addDocument('@param ' . $arg->type . ' $' . $arg->name);
		}
		$method->addDocument('@return ' . $meta->getReturnType());

		$method->addBody('throw new \Exception("Not implemented");');
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\Property $property
	 */
	protected function buildEntityPropertyCollectionAddMethod(Code\ClassType $type, Property $property)
	{
		$method = $type->addMethod('add' . ucfirst($property->getSingularizedName()));
		$param = $method->addParameter($property->getSingularizedName());
		$param->setTypeHint($property->getTypeHint());

		$method->addDocument('@param ' . $property->getTypeName() . ' $' . $property->getSingularizedName());
		$this->fluent && $method->addDocument('@return ' . $property->parentType->name);

		$method->addBody('$this->?->add($?);', array(
			$property->name,
			$property->getSingularizedName()
		));
		$this->fluent && $method->addBody('return $this;');
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\Property $property
	 */
	protected function buildEntityPropertyCollectionRemoveMethod(Code\ClassType $type, Property $property)
	{
		$method = $type->addMethod('remove' . ucfirst($property->getSingularizedName()));
		$param = $method->addParameter($property->getSingularizedName());
		$param->setTypeHint($property->getTypeHint());

		$method->addDocument('@param ' . $property->getTypeName() . ' $' . $property->getSingularizedName());
		$this->fluent && $method->addDocument('@return ' . $property->parentType->name);

		$method->addBody('$this->?->removeElement($?);', array(
			$property->name,
			$property->getSingularizedName()
		));
		$this->fluent && $method->addBody('return $this;');
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\Property $property
	 */
	protected function buildEntityPropertyCollectionGetMethod(Code\ClassType $type, Property $property)
	{
		$method = $type->addMethod('get' . ucfirst($property->getSingularizedName()));

		$method->addDocument('@return ' . $property->getTypeName() . '[]');

		$method->addBody('return $this->?->toArray();', array(
			$property->name,
		));
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\Property $property
	 */
	protected function buildEntityPropertyGetMethod(Code\ClassType $type, Property $property)
	{
		$method = $type->addMethod('get' . ucfirst($property->getSingularizedName()));

		$method->addDocument('@return ' . $property->getTypeName());

		$method->addBody('return $this->?;', array(
			$property->name,
		));
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\Property $property
	 */
	protected function buildEntityPropertySetMethod(Code\ClassType $type, Property $property)
	{
		$method = $type->addMethod('set' . ucfirst($property->getSingularizedName()));
		$param = $method->addParameter($property->name);
		$param->setTypeHint($property->getTypeHint());

		$method->addDocument('@param ' . $property->getTypeName() . ' $' . $property->name);
		$this->fluent && $method->addDocument('@return ' . $property->parentType->name);

		$method->addBody('$this->? = $?;', array(
			$property->name,
			$property->name
		));
		$this->fluent && $method->addBody('return $this;');
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\InterfaceType $meta
	 */
	protected function buildInterface(Code\ClassType $type, InterfaceType $meta)
	{
		$type->type = 'interface';
		foreach ($meta->extends as $extended) {
			$type->extends[] = $meta->getRelativeName($extended);
		}

		foreach ($meta->methods as $method) {
			$this->buildInterfaceMethod($type, $method);
		}
	}



	/**
	 * @param \Nette\Utils\PhpGenerator\ClassType $type
	 * @param \Kdyby\Violet\Method $meta
	 */
	protected function buildInterfaceMethod(Code\ClassType $type, Method $meta)
	{
		$method = $type->addMethod($meta->name);
		$method->setVisibility($meta->visibility);

		foreach ($meta->getFormatedArgs() as $arg) {
			$method->addParameter($arg->name)
				->setTypeHint($arg->type);
		}

		foreach ($meta->getFormatedArgs() as $arg) {
			$method->addDocument('@param ' . $arg->type . ' $' . $arg->name);
		}
		$method->addDocument('@return ' . $meta->getReturnType());
	}

}
