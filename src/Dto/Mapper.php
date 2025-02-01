<?php

namespace Neuron\Dto;

use Countable;
use DeepCopy\DeepCopy;
use DeepCopy\TypeFilter\Spl\ArrayObjectFilter;
use Neuron\Log\Log;

class Mapper
{
	private Dto		$_Dto;
	private string	$_Name;
	private array	$_Aliases = [];
	private array	$_Fields;
	private array	$_Properties;
	private bool 	$_StrictErrors = false;
	private bool	$_StrictMapping = false;

	public function __construct()
	{
	}

	/**
	 * @return bool
	 */

	public function isStrictErrors(): bool
	{
		return $this->_StrictErrors;
	}

	/**
	 * StrictErrors generates a type error immediately instead of returning the errors in batch.
	 *
	 * @param bool $Strict
	 * @return $this
	 */

	public function setStrictErrors( bool $Strict ): Mapper
	{
		$this->_StrictErrors = $Strict;
		return $this;
	}

	/**
	 * StrictMapping generates an error for missing mapping.
	 *
	 * @return bool
	 */

	public function isStrictMapping(): bool
	{
		return $this->_StrictMapping;
	}

	/**
	 * @param bool $StrictMapping
	 * @return $this
	 */

	public function setStrictMapping( bool $StrictMapping ): Mapper
	{
		$this->_StrictMapping = $StrictMapping;
		return $this;
	}

	/**
	 * @return array
	 */

	public function getFields() : array
	{
		return $this->_Fields;
	}

	/**
	 * @return array
	 */

	public function getProperties() : array
	{
		return $this->_Properties;
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

	public function setName( string $Name ): Mapper
	{
		$this->_Name = $Name;
		return $this;
	}

	/**
	 * Sets the mapping alias for a property key.
	 *
	 * @param string $PropertyName
	 * @param string $AliasName
	 * @return $this
	 */

	public function setAlias( string $PropertyName, string $AliasName ): Mapper
	{
		$this->_Aliases[ $AliasName ] = $PropertyName;

		return $this;
	}

	/**
	 * Gets the property key for a given alias.
	 *
	 * @param string $AliasName
	 * @return string|null
	 */

	public function getAlias( string $AliasName ): ?string
	{
		return $this->_Aliases[ $AliasName ] ?? null;
	}

	/**
	 * Assign data to a dto based on specific field mapping.
	 *
	 * @param Dto $Dto
	 * @param array $Data
	 * @return Dto
	 * @throws ValidationException|MapNotFoundException
	 */

	public function map( Dto $Dto, array $Data ) : Dto
	{
		$this->_Dto = $Dto;

		Log::debug( "Mapping {$Dto->getName()}..." );

		$Dto->clearErrors();

		$this->mapDto( $Dto, $Data );

		Log::debug( "Mapping complete." );

		return $Dto;
	}

	/**
	 * Recursively turns a nested dictionary into a single dimensional dictionary with flat keys.
	 * The result is a one level dictionary where each field is keyed such that
	 * level1.level2.level3.parameter = value.
	 *
	 * @param array $Array
	 * @param string|null $CurrentKey
	 * @return void
	 */

	public function flattenFields( array $Array, ?string $CurrentKey = null ): void
	{
		foreach( $Array as $Key => $Value )
		{
			if( $CurrentKey )
			{
				$MasterKey = $CurrentKey.'.'.$Key;
			}
			else
			{
				$MasterKey = $Key;
			}

			if( is_array( $Value ) )
			{
				$this->flattenFields( $Value, $MasterKey );
			}
			else
			{
				$this->_Fields[ $MasterKey ] = $Value;
			}

			$MasterKey = $CurrentKey;
		}
	}

	/**
	 * Recursively turns a dto into a single dimensional dictionary with flat keys.
	 * The result is a one level dictionary where each field is keyed such that
	 * level1.level2.level3.parameter = parameter.
	 *
	 * @param Dto $Dto
	 * @param string|null $MasterKey
	 * @return void
	 */

	public function flattenProperties( ICompound $Dto, ?string $MasterKey = null ): void
	{
		$MasterKey .= ( $MasterKey ? '.' : '' ).$Dto->getName();

		$Properties = $Dto->getProperties();

		/** @var Property $Property */
		foreach( $Properties as $Property )
		{
			$Key = $MasterKey.'.'.$Property->getName();

			if( $Property->getType() == 'object' )
			{
				$this->_Properties[ $Key ] = $Property;
				$this->flattenProperties( $Property->getValue(), $MasterKey );
			}
			elseif( $Property->getType() == 'array' )
			{
				$Template = $Property->getValue()->getItemTemplate();

				if( $Template->getType() == 'object')
				{
					$this->_Properties[ $Key ] = $Property;
					$Template->getValue()->setName( $Property->getName() );
					$this->flattenProperties( $Template->getValue(), $MasterKey );
				}
				elseif( $Template->getType() == 'array')
				{
					Log::error( "$Key: Array of arrays is not supported.");
				}
				else
				{
					$this->_Properties[ $Key ] = $Property;
				}
			}
			else
			{
				$this->_Properties[ $Key ] = $Property;
			}
		}
	}

	/**
	 * Gets a dto parameter based on its assigned alias.
	 *
	 * @param $Key
	 * @return Property|null
	 */

	protected function getPropertyByAlias( string $Key ) : ?Property
	{
		$Alias = $this->getAlias( $Key );

		if( !$Alias )
			Log::warning( "Alias not found for key: $Key." );

		return $this->_Properties[ $Alias ] ?? null;
	}

	/**
	 * Gets a dto by its assigned key
	 *
	 * @param string $Key
	 * @return Property|null
	 */
	protected function getPropertyByKey( string $Key ) : ?Property
	{
		return $this->_Properties[ $Key ] ?? null;
	}

	/**
	 * Build a the data that represents a single array step within an assignment.
	 *
	 * @param string $ArrayKey
	 * @param int $Element
	 * @param string $Name
	 * @return array
	 * @throws MapNotFoundException
	 */

	protected function buildArrayPart( string $ArrayKey, int $Element, string $Name ): array
	{
		$ArrayAlias = $this->getAlias( $ArrayKey );

		if( !$ArrayAlias )
		{
			$Message = "Missing map for '{$ArrayKey}'";
			Log::warning( $Message );
			throw new MapNotFoundException( $Message );
		}

		$Parts = explode( '.', $ArrayAlias );

		if( strlen( $Name ) )
		{
			$TempKey = $ArrayKey.'.'.$Name;

			$TempKey = $this->getAlias( $TempKey );

			$ChildParts	= explode( '.', $TempKey );;
			$Name 		= $ChildParts[ count( $ChildParts ) - 1 ];
		}

		return [
			'Element'	=> $Element,
			'ArrayKey'	=> $ArrayAlias,
			'Name'		=> $Name ?? ""
		];
	}

	/**
	 * Returns true if the Key references an array.
	 *
	 * @param string $Key
	 * @return bool
	 */

	protected function isArray( string $Key ) : bool
	{
		$Parts = explode( '.', $Key );

		foreach( $Parts as $Index => $Part )
		{
			if( ctype_digit( $Part ) )
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
	 * @param string $Key
	 * @return array|null
	 * @throws MapNotFoundException
	 */

	public function getArrayPath( string $Key ) : ?array
	{
		$Data		= [];
		$Parts		= explode( '.', $Key );
		$ArrayKey	= '';

		foreach( $Parts as $Index => $Part )
		{
			if( ctype_digit( $Part ) )
			{
				$Element	= (int)$Part;

				if( $Index + 1 <= count( $Parts ) - 1 )
				{
					$Part = $Parts[ $Index + 1 ];
				}
				else
				{
					$Part = '';
				}

				$Data[] = $this->buildArrayPart( $ArrayKey, $Element, $Part );
			}
			else
			{
				$ArrayKey .= ( $ArrayKey ? '.' : '' ) . $Part;
			}
		}

		return $Data;
	}

	/**
	 * Internal mapping method.
	 *
	 * @param Dto $Dto
	 * @param array $Data
	 * @return void
	 * @throws ValidationException|MapNotFoundException
	 */

	private function mapDto( Dto $Dto, array $Data ): void
	{
		$this->createDataMap( $Dto, $Data );

		foreach( $this->_Aliases as $Alias => $ParamName )
		{
			if( !$this->getPropertyByAlias( $Alias ) )
			{
				Log::warning( "Missing parameter for map $Alias : $ParamName" );
			}
		}

		foreach( $this->getFields() as $Key => $Value )
		{
			if( $this->isArray( $Key ) )
			{
				try
				{
					$Array = $this->getArrayPath( $Key );
					$this->mapArray( $Array, $Value );
				}
				catch( MapNotFoundException $Exception )
				{
				}
			}
			else
			{
				$this->mapScalar( $Key, $Value );
			}
		}

		$Dto->validate();
	}

	/**
	 * Map a non-array variable.
	 *
	 * @param int|string $Key
	 * @param mixed $Value
	 * @return void
	 * @throws ValidationException|MapNotFoundException
	 */

	protected function mapScalar( int|string $Key, mixed $Value ): void
	{
		$Property = $this->getPropertyByAlias( $Key );

		if( $Property )
		{
			$Parent = $Property->getParent()?->getParent();

			if( $Parent && get_class( $Parent ) == 'Neuron\Dto\Collection' )
			{
				/**
				 * This handles a scalar that is mapped to an array.
				 * It will create a new array item in the dto and assign the correct
				 * property.
				 */

				/**
				 * @todo iterate through any existing array items and find a matching named property
				 * that has no value. If so, set that one. If not, create a new item.
				 */

				foreach( $Parent->getChildren() as $Child )
				{
					$Target = $Child->getProperty( $Property->getName() );
					if( !$Target->getValue() )
					{
						$Target->setValue( $Value );
						return;
					}
				}

				$Template = $Parent->getItemTemplate();
				$DeepCopy = new DeepCopy();
				$Item = $DeepCopy->copy( $Template->getValue() );
				$Parent->addChild( $Item );
				$Target = $Item->getProperty( $Property->getName() );
				$Target->setValue( $Value );
			}
			else
			{
				$Property->setValue( $Value );
			}
		}
		elseif( $this->isStrictMapping())
		{
			throw new MapNotFoundException( $Key );
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
	 * @param array $ArrayData
	 * @param mixed $Value
	 * @return void
	 * @throws ValidationException
	 * @throws \Exception
	 */

	protected function mapArray( array $ArrayData, mixed $Value ): void
	{
		$Array = null;

		foreach( $ArrayData as $ArrayPart )
		{
			Log::debug( "Mapping key: {$ArrayPart['ArrayKey']}', Index: {$ArrayPart['Element']}, Property: {$ArrayPart['Name']}" );

			if( $Array === null )
			{
				$Array = $this->getPropertyByKey( $ArrayPart[ 'ArrayKey' ] );
			}

			$ArrayItem = $Array->getValue()->getChild( $ArrayPart[ 'Element' ] );

			if( $ArrayItem === null )
			{
				// If array element doesn't exist, crate it by cloning the item template.

				$Template = $Array->getValue()->getItemTemplate();

				$DeepCopy = new DeepCopy();

				if( is_object( $Template->getValue() ) )
				{
					// if it's an object, close the composite stored in the template value.

					$ArrayItem = $DeepCopy->copy( $Template->getValue() );

					$Array->getValue()
						  ->addChild( $ArrayItem );

					$ArrayItem = $ArrayItem->getProperty( $ArrayPart[ 'Name' ] );
				}
				else
				{
					// if it's a scalar value, clone the template and store
					// the property as a parameter.

					$ArrayItem = $DeepCopy->copy( $Template );

					$Array->getValue()
						  ->addChild( $ArrayItem );
				}
			}
			else
			{
				if( get_class( $ArrayItem ) == Dto::class )
				{
					// If the object is a dto then set the next item as the property
					// object specified by the name.

					$ArrayItem = $ArrayItem->getProperty( $ArrayPart[ 'Name' ] );
				}
			}

			$Array = $ArrayItem;
		}

		$Array->setValue( $Value );
	}

	/**
	 * @param Dto $Dto
	 * @param array $Data
	 * @return void
	 */
	public function createDataMap( Dto $Dto, array $Data ): void
	{
		$this->flattenProperties( $Dto );
		$this->flattenFields( $Data );
	}
}
