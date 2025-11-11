<?php

namespace Neuron\Dto\Compound;

use Neuron\Dto\Dto;

/**
 * Interface for compound data types
 *
 * Compound data types are DTOs or arrays.
 */

interface ICompound
{
	/**
	 * @return string
	 */

	public function getName(): string;

	/**
	 * @param string $name
	 * @return $this
	 */

	public function setName( string $name ): ICompound;

	/**
	 * @return ICompound|null
	 */

	public function getParent(): ?ICompound;

	/**
	 * @param Dto|null $parent
	 * @return ICompound
	 */

	public function setParent( ?Dto $parent ): ICompound;

	/**
	 * Adds a validation error to the list.
	 *
	 * @param array $errors
	 * @return ICompound
	 */

	public function addErrors( array $errors ): ICompound;

	/**
	 * Returns a list of validation errors for all parameter values.
	 *
	 * @return array
	 */

	public function getErrors(): array;

	/**
	 * Resets the error list.
	 *
	 * @return void
	 */

	public function clearErrors(): void;
}
