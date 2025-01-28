<?php

namespace Neuron\Dto;

use Neuron\Dto\ICompound;

class CompoundBase implements ICompound
{
	private string $_Name;
	private array $_Errors = [];
	private ?ICompound $_Parent = null;

	/**
	 * @return string
	 */

	public function getName(): string
	{
		return $this->_Name;
	}

	/**
	 * @param string $Name
	 * @return ICompound
	 */

	public function setName( string $Name ): ICompound
	{
		$this->_Name = $Name;
		return $this;
	}

	/**
	 * @return ICompound|null
	 */

	public function getParent(): ?ICompound
	{
		return $this->_Parent;
	}

	/**
	 * @param Dto|null $Parent
	 * @return ICompound
	 */

	public function setParent( ?ICompound $Parent ): ICompound
	{
		$this->_Parent = $Parent;
		return $this;
	}

	/**
	 * Adds a validation error to the list.
	 *
	 * @param array $Errors
	 * @return ICompound
	 */

	public function addErrors( array $Errors) : ICompound
	{
		foreach( $Errors as $Error )
		{
			$this->_Errors[] = "{$this->getName()}.$Error";
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
		return $this->_Errors;
	}

	/**
	 * Resets the error list.
	 *
	 * @return void
	 */

	public function clearErrors(): void
	{
		$this->_Errors = [];
	}

}
