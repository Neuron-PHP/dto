<?php

namespace Neuron\Dto\Compound;

use Neuron\Dto\Dto;

/**
 * Base class for compound DTOs.
 */

class Base implements ICompound
{
	private string $name;
	private array $errors = [];
	private ?ICompound $parent = null;

	/**
	 * @return string
	 */

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return ICompound
	 */

	public function setName( string $name ): ICompound
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return ICompound|null
	 */

	public function getParent(): ?ICompound
	{
		return $this->parent;
	}

	/**
	 * @param Dto|null $parent
	 * @return ICompound
	 */

	public function setParent( ?ICompound $parent ): ICompound
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * Adds a validation error to the list.
	 *
	 * @param array $errors
	 * @return ICompound
	 */

	public function addErrors( array $errors) : ICompound
	{
		foreach( $errors as $error )
		{
			$this->errors[] = "{$this->getName()}.$error";
		}

		return $this;
	}

	/**
	 * Returns a list of validation errors for all parameter values.
	 *
	 * @return array
	 */

	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * Resets the error list.
	 *
	 * @return void
	 */

	public function clearErrors(): void
	{
		$this->errors = [];
	}

}
