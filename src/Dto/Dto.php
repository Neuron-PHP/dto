<?php

namespace Neuron\Dto;

use Neuron\Core\Exceptions;
use Neuron\Dto\Compound\Base;
use Neuron\Log\Log;

/**
 * DTO Class handles compound objects with named properties.
 */

class Dto extends Base
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
	 * @throws PropertyNotFound
	 */

	public function __get( string $Name ) : mixed
	{
		$Parameter = $this->getProperty( $Name );

		if( !$Parameter )
		{
			throw new Exceptions\PropertyNotFound( $Name );
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
	 * @throws PropertyNotFound
	 * @throws Validation
	 */

	public function __set( string $Name, mixed $Value ) : void
	{
		$Parameter = $this->getProperty( $Name );

		if( !$Parameter )
		{
			throw new Exceptions\PropertyNotFound( $Name );
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
	 * @throws Validation
	 */

	public function validate() : void
	{
		$Parameters = $this->getProperties();

		foreach( $Parameters as $Property )
		{
			$this->validateProperty( $Property );
		}

		foreach( $this->getErrors() as $Error )
		{
			Log::error( $Error );
		}
	}

	/**
	 * @param mixed $Property
	 * @return void
	 * @throws Validation
	 */

	protected function validateProperty( mixed $Property ): void
	{
		if( $Property->getType() == 'object' )
		{
			$this->validateDto( $Property->getValue() );
		}
		elseif( $Property->getType() == 'array' )
		{
			$this->validateArray( $Property );
		}
		else
		{
			$this->validateScalar( $Property );
		}
	}

	/**
	 * @param Dto $Dto
	 * @return void
	 * @throws Validation
	 */

	protected function validateDto( Dto $Dto ): void
	{
		$Dto->validate();
		$this->addErrors( $Dto->getErrors() );
	}

	/**
	 * @param Property $Property
	 * @return void
	 * @throws Validation
	 * @throws Exception
	 */

	protected function validateArray( Property $Property ): void
	{
		try
		{
			$Property->validate();
		}
		catch( Exceptions\Validation $Exception )
		{
			$this->addErrors( $Exception->errors );
		}

		$this->addErrors( $Property->getValue()->getErrors() );

		foreach( $Property->getValue()->getChildren() as $Item )
		{
			$this->validateScalar( $Item );
		}
	}

	/**
	 * @param mixed $Property
	 * @return void
	 */

	protected function validateScalar( mixed $Property ): void
	{
		try
		{
			$Property->validate();
		}
		catch( Exceptions\Validation $Exception )
		{
			$this->addErrors( $Exception->errors );
		}
	}

	/**
	 * @return string
	 */

	public function getAsJson(): string
	{
		$Result = '{';

		foreach( $this->getProperties() as $Property )
		{
			$Json = $Property->getAsJson();

			if( $Json )
			{
				$Result .= $Json . ',';
			}
		}

		$Result = substr($Result, 0, -1);

		return $Result.'}';
	}

	/**
	 * @return string
	 */

	public function __toString(): string
	{
		return $this->getAsJson();
	}
}
