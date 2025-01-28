<?php

namespace Neuron\Dto;

use Neuron\Log\Log;

/**
 * DTO Class handles compound objects with named properties.
 */

class Dto extends CompoundBase
{
	private array $_Properties = [];

	/**
	 * Dto constructor.
	 */

	public function __construct()
	{
	}


	/**
	 * Gets a list of all validation errors.
	 *
	 * @return array
	 */

	public function getProperties(): array
	{
		return $this->_Properties;
	}

	/**
	 * Magic method for accessing parameters via dto->parameter
	 *
	 * @param string $Name
	 * @return mixed
	 * @throws PropertyNotFoundException
	 */

	public function __get( string $Name ) : mixed
	{
		$Parameter = $this->getProperty( $Name );

		if( !$Parameter )
		{
			throw new PropertyNotFoundException( $Name );
		}

		if( $Parameter->getType() === 'array' )
		{
			$ItemType = $Parameter->getValue()->getItemTemplate()->getType();
			if( $ItemType !== 'array' && $ItemType !== 'object' )
			{
				$Items = [];
				foreach( $Parameter->getValue()->getChildren() as $Child )
				{
					$Items[] = $Child->getValue();
				}
				return $Items;
			}

			return $Parameter->getValue()->getChildren();
		}

		return $Parameter->getValue();
	}

	/**
	 * Magic method for setting parameter values via dto->parameter = value.
	 *
	 * @param string $Name
	 * @param mixed $Value
	 * @return void
	 * @throws PropertyNotFoundException
	 * @throws ValidationException
	 */

	public function __set( string $Name, mixed $Value ) : void
	{
		$Parameter = $this->getProperty( $Name );

		if( !$Parameter )
		{
			throw new PropertyNotFoundException( $Name );
		}

		$Parameter->setValue( $Value );
		$Parameter->validate();
	}

	/**
	 * Gets a parameter by name.
	 *
	 * @param string $Name
	 * @return Property|null
	 */

	public function getProperty( string $Name ): ?Property
	{
		return $this->_Properties[ $Name ] ?? null;
	}

	/**
	 * Sets a parameter by name.
	 *
	 * @param string $Name
	 * @param Property $Parameter
	 * @return $this
	 */

	public function setProperty( string $Name, Property $Parameter ): Dto
	{
		$this->_Properties[ $Name ] = $Parameter;

		return $this;
	}

	/**
	 * Validates the values for all parameters.
	 *
	 * @return void
	 */

	public function validate() : void
	{
		$Parameters = $this->getProperties();

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
