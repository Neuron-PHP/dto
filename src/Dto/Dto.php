<?php

namespace Neuron\Dto;

use Neuron\Log\Log;

/**
 * Class DTO
 */
class Dto
{
	private string $_Name;
	private array $_Parameters = [];
	private array $_Errors = [];
	private ?Dto $_Parent = null;

	/**
	 * Dto constructor.
	 */

	public function __construct()
	{
	}

	/**
	 * @return string
	 */

	public function getName(): string
	{
		return $this->_Name;
	}

	/**
	 * @param string $Name
	 * @return $this
	 */

	public function setName( string $Name ): Dto
	{
		$this->_Name = $Name;
		return $this;
	}

	/**
	 * @return Dto|null
	 */

	public function getParent(): ?Dto
	{
		return $this->_Parent;
	}

	/**
	 * @param Dto|null $Parent
	 * @return $this
	 */

	public function setParent( ?Dto $Parent ): Dto
	{
		$this->_Parent = $Parent;
		return $this;
	}

	/**
	 * Adds a validaton error to the list.
	 *
	 * @param array $Errors
	 * @return $this
	 */

	public function addErrors( array $Errors) : Dto
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

	/**
	 * Gets a list of all validation errors.
	 *
	 * @return array
	 */

	public function getParameters(): array
	{
		return $this->_Parameters;
	}

	/**
	 * Magic method for accessing parameters via dto->parameter
	 *
	 * @param string $Name
	 * @return mixed
	 * @throws ParameterNotFoundException
	 */

	public function __get( string $Name ) : mixed
	{
		$Parameter = $this->getParameter( $Name );

		if( !$Parameter )
		{
			throw new ParameterNotFoundException( $Name );
		}

		if( $Parameter->getType() === 'array' && count( $Parameter->getChildren() ) )
		{
			return $Parameter->getChildren();
		}

		return $Parameter->getValue();
	}

	/**
	 * Magic method for setting parameter values via dto->parameter = value.
	 *
	 * @param string $Name
	 * @param mixed $Value
	 * @return void
	 * @throws ParameterNotFoundException
	 * @throws ValidationException
	 */

	public function __set( string $Name, mixed $Value ) : void
	{
		$Parameter = $this->getParameter( $Name );

		if( !$Parameter )
		{
			throw new ParameterNotFoundException( $Name );
		}

		$Parameter->setValue( $Value );
		$Parameter->validate();
	}

	/**
	 * Gets a parameter by name.
	 *
	 * @param string $Name
	 * @return Parameter|null
	 */

	public function getParameter( string $Name ): ?Parameter
	{
		return $this->_Parameters[ $Name ] ?? null;
	}

	/**
	 * Sets a parameter by name.
	 *
	 * @param string $Name
	 * @param Parameter $Parameter
	 * @return $this
	 */

	public function setParameter( string $Name, Parameter $Parameter ): Dto
	{
		$this->_Parameters[ $Name ] = $Parameter;

		return $this;
	}

	/**
	 * Validates the values for all parameters.
	 *
	 * @return void
	 */

	public function validate() : void
	{
		$Parameters = $this->getParameters();

		foreach( $Parameters as $Parameter )
		{
			if( $Parameter->getType() == 'object')
			{
				$Dto = $Parameter->getValue();
				$Dto->validate();
				$this->addErrors( $Dto->getErrors() );
			}
			else
			{
				try
				{
					$Parameter->validate();
				}
				catch( ValidationException $Exception )
				{
					Log::warning( $Exception->getMessage() );
					$this->addErrors( $Exception->getErrors() );
				}
			}
		}
	}
}
