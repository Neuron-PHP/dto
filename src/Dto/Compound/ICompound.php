<?php

namespace Neuron\Dto\Compound;

use Neuron\Dto\Dto;

interface ICompound
{
	/**
	 * @return string
	 */

	public function getName(): string;

	/**
	 * @param string $Name
	 * @return $this
	 */

	public function setName( string $Name ): ICompound;

	/**
	 * @return ICompound|null
	 */

	public function getParent(): ?ICompound;

	/**
	 * @param Dto|null $Parent
	 * @return ICompound
	 */

	public function setParent( ?Dto $Parent ): ICompound;

	/**
	 * Adds a validation error to the list.
	 *
	 * @param array $Errors
	 * @return ICompound
	 */

	public function addErrors( array $Errors ): ICompound;

	public array $_Errors {
		get;
	}

	/**
	 * Resets the error list.
	 *
	 * @return void
	 */

	public function clearErrors(): void;
}
