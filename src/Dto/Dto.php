<?php

namespace Neuron\Dto;

use Neuron\Core\Exceptions;
use Neuron\Core\Exceptions\PropertyNotFound;
use Neuron\Core\Exceptions\Validation;
use Neuron\Dto\Compound\Base;
use Neuron\Log\Log;

/**
 * DTO Class handles compound objects with named properties.
 */

class Dto extends Base
{
	private array $properties = [];

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
		return $this->properties;
	}

	/**
	 * Magic method for accessing parameters via dto->parameter
	 *
	 * @param string $name
	 * @return mixed
	 * @throws PropertyNotFound
	 */

	public function __get( string $name ) : mixed
	{
		$parameter = $this->getProperty( $name );

		if( !$parameter )
		{
			throw new Exceptions\PropertyNotFound( $name );
		}

		if( $parameter->getType() === 'array' )
		{
			$itemType = $parameter->getValue()->getItemTemplate()->getType();
			if( $itemType !== 'array' && $itemType !== 'object' )
			{
				$items = [];
				foreach( $parameter->getValue()->getChildren() as $child )
				{
					$items[] = $child->getValue();
				}
				return $items;
			}

			return $parameter->getValue()->getChildren();
		}

		return $parameter->getValue();
	}

	/**
	 * Magic method for setting parameter values via dto->parameter = value.
	 *
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 * @throws Validation
	 * @throws PropertyNotFound
	 */

	public function __set( string $name, mixed $value ) : void
	{
		$parameter = $this->getProperty( $name );

		if( !$parameter )
		{
			throw new Exceptions\PropertyNotFound( $name );
		}

		$parameter->setValue( $value );
		$parameter->validate();
	}

	/**
	 * Gets a parameter by name.
	 *
	 * @param string $name
	 * @return Property|null
	 */

	public function getProperty( string $name ): ?Property
	{
		return $this->properties[ $name ] ?? null;
	}

	/**
	 * Sets a parameter by name.
	 *
	 * @param string $name
	 * @param Property $parameter
	 * @return $this
	 */

	public function setProperty( string $name, Property $parameter ): Dto
	{
		$this->properties[ $name ] = $parameter;

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
		$parameters = $this->getProperties();

		foreach( $parameters as $property )
		{
			$this->validateProperty( $property );
		}

		foreach( $this->getErrors() as $error )
		{
			Log::error( $error );
		}
	}

	/**
	 * @param mixed $property
	 * @return void
	 * @throws Validation
	 */

	protected function validateProperty( mixed $property ): void
	{
		if( $property->getType() == 'object' || $property->getType() == 'dto' )
		{
			$this->validateDto( $property->getValue() );
		}
		elseif( $property->getType() == 'array' )
		{
			$this->validateArray( $property );
		}
		else
		{
			$this->validateScalar( $property );
		}
	}

	/**
	 * @param Dto $dto
	 * @return void
	 * @throws Validation
	 */

	protected function validateDto( Dto $dto ): void
	{
		$dto->validate();
		$this->addErrors( $dto->getErrors() );
	}

	/**
	 * @param Property $property
	 * @return void
	 * @throws Validation
	 */

	protected function validateArray( Property $property ): void
	{
		try
		{
			$property->validate();
		}
		catch( Exceptions\Validation $exception )
		{
			$this->addErrors( $exception->errors );
		}

		$value = $property->getValue();

		// Only validate Collection items (typed arrays)
		// Untyped arrays (raw PHP arrays) are validated by the property validator
		if( $value instanceof Collection )
		{
			$this->addErrors( $value->getErrors() );

			foreach( $value->getChildren() as $item )
			{
				$this->validateScalar( $item );
			}
		}
	}

	/**
	 * @param mixed $property
	 * @return void
	 */

	protected function validateScalar( mixed $property ): void
	{
		try
		{
			$property->validate();
		}
		catch( Exceptions\Validation $exception )
		{
			$this->addErrors( $exception->errors );
		}
	}

	/**
	 * @return string
	 */

	public function getAsJson(): string
	{
		$result = '{';

		foreach( $this->getProperties() as $property )
		{
			$json = $property->getAsJson();

			if( $json )
			{
				$result .= $json . ',';
			}
		}

		$result = substr($result, 0, -1);

		return $result.'}';
	}

	/**
	 * @return string
	 */

	public function __toString(): string
	{
		return $this->getAsJson();
	}
}
