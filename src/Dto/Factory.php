<?php

namespace Neuron\Dto;

use Exception;
use Neuron\Dto\Compound\ICompound;
use Symfony\Component\Yaml\Yaml;

/**
 * Factory class for creating Data Transfer Objects (DTOs) from YAML configuration files or arrays.
 *
 * This factory can read YAML files containing DTO definitions OR accept arrays with DTO definitions,
 * and creates corresponding Dto objects with properties, validation rules, and nested structures.
 *
 * @package Neuron\Dto
 */
class Factory
{
	private string|array $source;

	/**
	 * @param string|array $source YAML file path or array of DTO properties
	 */

	public function __construct( string|array $source )
	{
		$this->source = $source;
	}

	/**
	 * @return string|array
	 */

	public function getSource(): string|array
	{
		return $this->source;
	}

	/**
	 * @throws Exception
	 */

	public function create(): Dto
	{
		if( is_string( $this->source ) )
		{
			// Load from YAML file
			$name = pathinfo( $this->source )[ 'filename' ];
			$data = Yaml::parseFile( $this->source );
			return $this->createDto( $name, $data[ 'dto' ] );
		}
		else
		{
			// Load from array
			// Only check for 'name' if using structured format with 'properties' key
			// This prevents conflict with 'name' as an actual property
			if( isset( $this->source[ 'properties' ] ) )
			{
				$name = $this->source[ 'name' ] ?? 'InlineDto';
				$properties = $this->source[ 'properties' ];
			}
			else
			{
				// Flat format - entire array is properties
				$name = 'InlineDto';
				$properties = $this->source;
			}
			return $this->createDto( $name, $properties );
		}
	}

	/**
	 * @param string $name
	 * @param array $data
	 * @return Dto
	 * @throws Exception
	 */

	protected function createDto( string $name, array $data ) : Dto
	{
		$dto = new Dto();

		$dto->setName( $name );

		foreach( $data as $name => $paramData )
		{
			$property = $this->createProperty( $name, $paramData, $dto );
			$dto->setProperty( $property->getName(), $property  );
		}

		return $dto;
	}

	/**
	 * @param string $name
	 * @param array $data
	 * @return Collection
	 * @throws Validation
	 */

	protected function createArray( string $name, array $data ): Collection
	{
		$collection = new Collection();

		$collection->setName( $name );

		$collection->setItemTemplate( $this->createProperty( 'item', $data, $collection ) );

		return $collection;
	}

	/**
	 * @param int|string $name
	 * @param array $propertyData
	 * @param Dto $parent
	 * @return Property
	 * @throws Validation
	 */

	protected function createProperty( int|string $name, array $propertyData, ICompound $parent ): Property
	{
		$property = new Property();
		$property->setName( $name );
		$property->setParent( $parent );

		if( isset( $propertyData[ 'required' ] ) )
		{
			$property->setRequired( $propertyData[ 'required' ] );
		}

		$property->setType( $propertyData[ 'type' ] );

		if( $property->getType() === 'object' )
		{
			$paramDto = $this->createDto( $name, $propertyData[ 'properties' ] );
			$paramDto->setParent( $parent );
			$property->setValue( $paramDto );
		}

		if( $property->getType() === 'array' )
		{
			$paramDto = $this->createArray( $name, $propertyData[ 'items' ] );
			if( isset( $propertyData[ 'max' ] ) )
			{
				$max = $propertyData[ 'max' ];
				$paramDto->setRange( 0, $max );
			}

			$paramDto->setParent( $parent );
			$property->setValue( $paramDto );
		}

		if( isset( $propertyData[ 'length' ] ) )
		{
			$this->setLengthRange( $propertyData, $property );
		}

		if( isset( $propertyData[ 'range' ] ) )
		{
			$this->setValueRange( $propertyData, $property );
		}

		if( isset( $propertyData[ 'pattern' ] ) )
		{
			$property->setPattern( $propertyData[ 'pattern' ] );
		}

		return $property;
	}

	/**
	 * @param array $propertyData
	 * @param Property $property
	 * @return Property
	 */

	protected function setLengthRange( array $propertyData, Property $property ): Property
	{
		$min = $max = 0;

		if( isset( $propertyData[ 'length' ][ 'min' ] ) )
		{
			$min = $propertyData[ 'length' ][ 'min' ];
		}

		if( isset( $propertyData[ 'length' ][ 'max' ] ) )
		{
			$max = $propertyData[ 'length' ][ 'max' ];
		}

		$property->setLengthRange( $min, $max );

		return $property;
	}

	/**
	 * @param array $propertyData
	 * @param Property $property
	 * @return mixed
	 */

	protected function setValueRange( array $propertyData, Property $property ): mixed
	{
		$min = $max = 0;

		if( isset( $propertyData[ 'range' ][ 'min' ] ) )
		{
			$min = $propertyData[ 'range' ][ 'min' ];
		}

		if( isset( $propertyData[ 'range' ][ 'max' ] ) )
		{
			$max = $propertyData[ 'range' ][ 'max' ];
		}

		$property->setValueRange( $min, $max );

		return $property;
	}
}
