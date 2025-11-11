<?php

namespace Neuron\Dto\Mapper;

use DeepCopy\DeepCopy;
use Neuron\Core\Exceptions;
use Neuron\Core\Exceptions\MapNotFound;
use Neuron\Core\Exceptions\NotFound;
use Neuron\Core\Exceptions\Validation;
use Neuron\Dto\Compound\ICompound;
use Neuron\Dto\Dto;
use Neuron\Dto\Property;
use Neuron\Log\Log;

class Dynamic implements IMapper
{
	private string	$name;
	private array	$aliases = [];
	private array	$fields;
	private array	$properties;
	private bool 	$strictErrors = false;
	private bool	$strictMapping = false;

	public function __construct()
	{
	}

	/**
	 * @return bool
	 */

	public function isStrictErrors(): bool
	{
		return $this->strictErrors;
	}

	/**
	 * StrictErrors generates a type error immediately instead of returning the errors in batch.
	 *
	 * @param bool $strict
	 * @return $this
	 */

	public function setStrictErrors( bool $strict ): Dynamic
	{
		$this->strictErrors = $strict;
		return $this;
	}

	/**
	 * StrictMapping generates an error for missing mapping.
	 *
	 * @return bool
	 */

	public function isStrictMapping(): bool
	{
		return $this->strictMapping;
	}

	/**
	 * @param bool $strictMapping
	 * @return $this
	 */

	public function setStrictMapping( bool $strictMapping ): Dynamic
	{
		$this->strictMapping = $strictMapping;
		return $this;
	}

	/**
	 * @return array
	 */

	public function getFields() : array
	{
		return $this->fields;
	}

	/**
	 * @return array
	 */

	public function getProperties() : array
	{
		return $this->properties;
	}

	/**
	 * @return string
	 */

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return $this
	 */

	public function setName( string $name ): Dynamic
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * Sets the mapping alias for a property key.
	 *
	 * @param string $propertyName
	 * @param string $aliasName
	 * @return $this
	 */

	public function setAlias( string $propertyName, string $aliasName ): Dynamic
	{
		$this->aliases[ $aliasName ] = $propertyName;

		return $this;
	}

	/**
	 * Gets the property key for a given alias.
	 *
	 * @param string $aliasName
	 * @return string|null
	 */

	public function getAlias( string $aliasName ): ?string
	{
		return $this->aliases[ $aliasName ] ?? null;
	}

	/**
	 * Assign data to a dto based on specific field mapping.
	 *
	 * @param Dto $dto
	 * @param array $data
	 * @return Dto
	 * @throws MapNotFound
	 * @throws Validation
	 * @throws \Neuron\Dto\Validation
	 */

	public function map( Dto $dto, array $data ) : Dto
	{
		Log::debug( "Mapping {$dto->getName()}..." );

		$dto->clearErrors();

		$this->mapDto( $dto, $data );

		Log::debug( "Mapping complete." );

		return $dto;
	}

	/**
	 * Recursively turns a nested dictionary into a single dimensional dictionary with flat keys.
	 * The result is a one level dictionary where each field is keyed such that
	 * level1.level2.level3.parameter = value.
	 *
	 * @param array $array
	 * @param string|null $currentKey
	 * @return void
	 */

	public function flattenFields( array $array, ?string $currentKey = null ): void
	{
		foreach( $array as $key => $value )
		{
			if( $currentKey )
			{
				$masterKey = $currentKey.'.'.$key;
			}
			else
			{
				$masterKey = $key;
			}

			if( is_array( $value ) )
			{
				$this->flattenFields( $value, $masterKey );
			}
			else
			{
				$this->fields[ $masterKey ] = $value;
			}
		}
	}

	/**
	 * @param Property $property
	 * @param string $key
	 * @param string $masterKey
	 * @return void
	 */

	protected function flattenArray( Property $property, string $key, string $masterKey ): void
	{
		$template = $property->getValue()
									->getItemTemplate();

		if( $template->getType() == 'object' )
		{
			$this->properties[ $key ] = $property;
			$template->getValue()
						->setName( $property->getName() );
			$this->flattenProperties( $template->getValue(), $masterKey );
		}
		elseif( $template->getType() == 'array' )
		{
			Log::error( "$key: Array of arrays are not supported." );
		}
		else
		{
			$this->properties[ $key ] = $property;
		}
	}

	/**
	 * Recursively turns a dto into a single dimensional dictionary with flat keys.
	 * The result is a one level dictionary where each field is keyed such that
	 * level1.level2.level3.parameter = parameter.
	 *
	 * @param Dto $dto
	 * @param string|null $masterKey
	 * @return void
	 */

	public function flattenProperties( ICompound $dto, ?string $masterKey = null ): void
	{
		$masterKey .= ( $masterKey ? '.' : '' ).$dto->getName();

		$properties = $dto->getProperties();

		/** @var Property $property */
		foreach( $properties as $property )
		{
			$key = $masterKey.'.'.$property->getName();

			if( $property->getType() == 'object' )
			{
				$this->properties[ $key ] = $property;
				$this->flattenProperties( $property->getValue(), $masterKey );
			}
			elseif( $property->getType() == 'array' )
			{
				$this->flattenArray( $property, $key, $masterKey );
			}
			else
			{
				$this->properties[ $key ] = $property;
			}
		}
	}

	/**
	 * Gets a dto parameter based on its assigned alias.
	 *
	 * @param string $key
	 * @return Property|null
	 */

	protected function getPropertyByAlias( string $key ) : ?Property
	{
		$alias = $this->getAlias( $key );

		if( !$alias )
			Log::warning( "Alias not found for key: $key." );

		return $this->properties[ $alias ] ?? null;
	}

	/**
	 * Gets a dto by its assigned key
	 *
	 * @param string $key
	 * @return Property|null
	 */

	protected function getPropertyByKey( string $key ) : ?Property
	{
		return $this->properties[ $key ] ?? null;
	}

	/**
	 * Build a the data that represents a single array step within an assignment.
	 *
	 * @param string $arrayKey
	 * @param int $element
	 * @param string $name
	 * @return array
	 * @throws MapNotFound
	 */

	protected function buildArrayPart( string $arrayKey, int $element, string $name ): array
	{
		$arrayAlias = $this->getAlias( $arrayKey );

		if( !$arrayAlias )
		{
			$message = "Missing map for '{$arrayKey}'";
			Log::warning( $message );
			throw new Exceptions\NotFound( $message );
		}

		if( strlen( $name ) )
		{
			$tempKey = $arrayKey.'.'.$name;

			$tempKey = $this->getAlias( $tempKey );

			$childParts	= explode( '.', $tempKey );;
			$name 		= $childParts[ count( $childParts ) - 1 ];
		}

		return [
			'Element'	=> $element,
			'ArrayKey'	=> $arrayAlias,
			'Name'		=> $name ?? ""
		];
	}

	/**
	 * Returns true if the Key references an array.
	 *
	 * @param string $key
	 * @return bool
	 */

	protected function isArray( string $key ) : bool
	{
		$parts = explode( '.', $key );

		foreach( $parts as $index => $part )
		{
			if( ctype_digit( $part ) )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Returns an array that represents the individual stages of single and multidimensional arrays.
	 * ArrayKey points to the array.
	 * Element is the index of the array element.
	 * ItemKey points to the parameter being set in the array element.
	 * Name is the de-aliased name of the parameter to be set.
	 *
	 * @param string $key
	 * @return array|null
	 * @throws MapNotFound
	 */

	public function getArrayPath( string $key ) : ?array
	{
		$data		= [];
		$parts		= explode( '.', $key );
		$arrayKey	= '';

		foreach( $parts as $index => $part )
		{
			if( ctype_digit( $part ) )
			{
				$element	= (int)$part;

				if( $index + 1 <= count( $parts ) - 1 )
				{
					$part = $parts[ $index + 1 ];
				}
				else
				{
					$part = '';
				}

				$data[] = $this->buildArrayPart( $arrayKey, $element, $part );
			}
			else
			{
				$arrayKey .= ( $arrayKey ? '.' : '' ) . $part;
			}
		}

		return $data;
	}

	/**
	 * Internal mapping method.
	 *
	 * @param Dto $dto
	 * @param array $data
	 * @return void
	 * @throws Exceptions\Validation | Exceptions\MapNotFound|\Neuron\Dto\Validation
	 */

	private function mapDto( Dto $dto, array $data ): void
	{
		$this->createDataMap( $dto, $data );

		foreach( $this->aliases as $alias => $paramName )
		{
			if( !$this->getPropertyByAlias( $alias ) )
			{
				Log::warning( "Missing parameter for map $alias : $paramName" );
			}
		}

		foreach( $this->getFields() as $key => $value )
		{
			if( $this->isArray( $key ) )
			{
				try
				{
					$array = $this->getArrayPath( $key );
					$this->mapArray( $array, $value );
				}
				catch( Exceptions\NotFound $exception )
				{
				}
			}
			else
			{
				$this->mapScalar( $key, $value );
			}
		}

		$dto->validate();
	}

	/**
	 * Map a non-compound variable.
	 *
	 * @param int|string $key
	 * @param mixed $value
	 * @return void
	 * @throws NotFound
	 */

	protected function mapScalar( int|string $key, mixed $value ): void
	{
		$property = $this->getPropertyByAlias( $key );

		if( $property === null )
		{
			if( $this->isStrictMapping() )
				throw new Exceptions\NotFound( $key );

			return;
		}

		$parent = $property->getParent()
								 ?->getParent();

		if( $parent === null )
		{
			$property->setValue( $value );
			return;
		}

		if( get_class( $parent ) == 'Neuron\Dto\Collection' )
		{
			/**
			 * This handles a scalar that is mapped to an array.
			 * It will create a new array item in the Collection and assign the correct
			 * property.
			 */

			/**
			 * Iterate through any existing array items and find a matching named property
			 * that has no value. If so, set that one. If not, create a new item and set
			 * the value.
			 */

			foreach( $parent->getChildren() as $child )
			{
				$target = $child->getProperty( $property->getName() );
				if( !$target->getValue() )
				{
					$target->setValue( $value );
					return;
				}
			}

			$template	= $parent->getItemTemplate();
			$deepCopy	= new DeepCopy();
			$item 		= $deepCopy->copy( $template->getValue() );

			$parent->addChild( $item );
			$item->getProperty( $property->getName() )
				  ->setValue( $value );
		}
	}

	/**
	 * Map single and multidimensional arrays.
	 * This method walks through an array that contains parts
	 * that represent an object and array offset within each part of the mapped field key
	 * as, each field key can reference different offsets from multiple nested arrays.
	 * A path must be walked through to get to the final value to be set and the
	 * ArrayData is the map.
	 *
	 * @param array $arrayData
	 * @param mixed $value
	 * @return void
	 * @throws Exceptions\Validation
	 */

	protected function mapArray( array $arrayData, mixed $value ): void
	{
		$array = null;

		foreach( $arrayData as $arrayPart )
		{
			Log::debug( "Mapping key: {$arrayPart['ArrayKey']}', Index: {$arrayPart['Element']}, Property: {$arrayPart['Name']}" );

			if( $array === null )
			{
				/**
				 * Array will only be non-null when a value is being assigned to an
				 * element in a multidimensional array. Each ArrayPart represents
				 * a step into an array in the chain with the final part locating the actual
				 * property to assign the value to.
				 */

				$array = $this->getPropertyByKey( $arrayPart[ 'ArrayKey' ] );
			}

			$arrayItem = $array->getValue()->getChild( $arrayPart[ 'Element' ] );

			if( $arrayItem === null )
			{
				/**
				 * If array element doesn't exist, crate it by cloning the item template
				 * then adding the clone as an item.
				 * The itemTemplate contains the properties of the compound or scalar
				 * that each item in the array will contain.
				 */

				$template = $array->getValue()->getItemTemplate();

				$deepCopy = new DeepCopy();

				if( is_object( $template->getValue() ) )
				{
					/**
					 * If it's an object, clone the composite stored in the template value.
					 */

					$arrayItem = $deepCopy->copy( $template->getValue() );

					$array->getValue()
						  ->addChild( $arrayItem );

					$arrayItem = $arrayItem->getProperty( $arrayPart[ 'Name' ] );
				}
				else
				{
					/**
					 * if it's a scalar value, clone the template and store
					 * the property as a parameter.
					 */

					$arrayItem = $deepCopy->copy( $template );

					$array->getValue()
						  ->addChild( $arrayItem );
				}
			}
			else
			{
				if( get_class( $arrayItem ) == Dto::class )
				{
					/**
					 * If the array item exists and it is a dto
					 * then set the next item to the dto property.
					 */

					$arrayItem = $arrayItem->getProperty( $arrayPart[ 'Name' ] );
				}
			}

			$array = $arrayItem;
		}

		$array->setValue( $value );
	}

	/**
	 * @param Dto $dto
	 * @param array $data
	 * @return void
	 */

	public function createDataMap( Dto $dto, array $data ): void
	{
		$this->flattenProperties( $dto );
		$this->flattenFields( $data );
	}
}
