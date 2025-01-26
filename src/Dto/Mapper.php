<?php

namespace Neuron\Dto;

use Neuron\Log\Log;

class Mapper
{
	private string	$_Name;
	private array $_Aliases = [];
	private array	$_Fields;
	private array	$_Parameters;
	private bool	$_StrictErrors = false;
	private bool	$_StrictMapping = false;

	public function __construct()
	{
		Log::setRunLevel( 'debug' );
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

	public function getParameters() : array
	{
		return $this->_Parameters;
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
	 * @throws ParameterNotFoundException|ValidationException
	 */

	public function map( Dto $Dto, array $Data ) : Dto
	{
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

	public function flattenParameters( Dto $Dto, ?string $MasterKey = null ): void
	{
		$MasterKey .= ( $MasterKey ? '.' : '' ).$Dto->getName();

		$Parameters = $Dto->getParameters();

		/** @var Parameter $Parameter */
		foreach( $Parameters as $Parameter )
		{
			$Key = $MasterKey.'.'.$Parameter->getName();

			if( $Parameter->getType() == 'object' || $Parameter->getType() == 'array' )
			{
				$this->_Parameters[ $Key ] = $Parameter;
				$this->flattenParameters( $Parameter->getValue(), $MasterKey );
			}
			else
			{
				$this->_Parameters[ $Key ] = $Parameter;
			}
		}
	}

	/**
	 * Gets a dto parameter based on its assigned alias.
	 *
	 * @param $Key
	 * @return Parameter|null
	 */

	protected function getParameterByAlias( string $Key ) : ?Parameter
	{
		$Alias = $this->getAlias( $Key );

		return $this->_Parameters[ $Alias ] ?? null;
	}

	/**
	 * Gets a dto by its assigned key
	 *
	 * @param string $Key
	 * @return Parameter|null
	 */
	protected function getParameterByKey( string $Key ) : ?Parameter
	{
		return $this->_Parameters[ $Key ] ?? null;
	}

	/**
	 * Build a the data that represents a single array step within an assignment.
	 *
	 * @param string $ChildKey
	 * @param string $ArrayKey
	 * @param int $Element
	 * @param string $Name
	 * @return array
	 * @throws ParameterNotFoundException
	 */

	protected function buildArrayPart( string $ChildKey, string $ArrayKey, int $Element, string $Name ): array
	{
		$ArrayAlias = $this->getAlias( $ArrayKey );
		$ChildAlias	= $this->getAlias( $ChildKey );

		if( strlen( $Name ) )
		{
			$TempKey = $ArrayKey.'.'.$Name;

			$TempKey = $this->getAlias( $TempKey );

			$ChildParts	= explode( '.', $TempKey );;
			$Name 		= $ChildParts[ count( $ChildParts ) - 1 ];

			$ChildAlias .= '.item.'.$Name;

			$Parameter = $this->getParameterByKey( $ChildAlias );
		}

		return [
			'Element'	=> $Element,
			'ArrayKey'	=> $ArrayAlias,
			'ChildKey'	=> $ChildAlias,
			'Name'		=> $Name ?? ""
		];
	}

	/**
	 * Returns true if the Key apparently references an array.
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
	 * ChildKey points to the parameter being set in the array element.
	 * Name is the de-aliased name of the parameter to be set.
	 *
	 * @param string $Key
	 * @return array|null
	 * @throws ParameterNotFoundException
	 */

	public function getArrayPath( string $Key ) : ?array
	{
		$Data				= [];
		$Element			= null;
		$Parts			= explode( '.', $Key );
		$ArrayKey		= '';
		$ChildKey		= '';
		$LastElement	= null;

		foreach( $Parts as $Index => $Part )
		{
			if( ctype_digit( $Part ) )
			{
				$Element			= (int)$Part;
				$LastElement	= $Element;
				$ArrayKey		= $ChildKey;

				if( $Index + 1 <= count( $Parts ) - 1 )
				{
					$Part = $Parts[ $Index + 1 ];
				}
				else
				{
					$Part = '';
				}

				$Data[] = $this->buildArrayPart( $ChildKey, $ArrayKey, $Element, $Part );
			}
			else
			{
				$Element		= null;
				$ArrayKey	= $ChildKey;
				$ChildKey	.= ( $ChildKey ? '.' : '' ) . $Part;
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
	 * @throws ParameterNotFoundException|ValidationException|MapNotFoundException
	 */

	private function mapDto( Dto $Dto, array $Data ): void
	{
		$this->flattenParameters( $Dto );
		$this->flattenFields( $Data );

		foreach( $this->_Aliases as $Alias => $ParamName )
		{
			if( !$this->getParameterByAlias( $Alias ) )
			{
				Log::warning( "Missing parameter for map $Alias : $ParamName" );
			}
		}

		foreach( $this->getFields() as $Key => $Value )
		{

			if( $this->isArray( $Key ) )
			{
				$Array = $this->getArrayPath( $Key );
				$this->mapArray( $Array, $Value );
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
		$Parameter = $this->getParameterByAlias( $Key );

		if( $Parameter )
		{
			$Parameter->setValue( $Value );
		}
		elseif( $this->isStrictMapping())
		{
			Log::error( 'Missing map for '.$Key );
			throw new MapNotFoundException( $Key );
		}
	}

	/**
	 * Map single and multidimensional arrays.
	 *
	 * @param array $Array
	 * @param mixed $Value
	 * @return void
	 * @throws ParameterNotFoundException|ValidationException
	 * @throws \Exception
	 */

	protected function mapArray( array $Array, mixed $Value ): void
	{
		$Parent = null;

		foreach( $Array as $Part )
		{
			Log::debug( "Mapping key: {$Part['ArrayKey']}', Template: {$Part['ChildKey']}, Index: {$Part['Element']}, Parameter: {$Part['Name']}" );

			if( $Parent === null )
			{
				$Parent = $this->getParameterByKey( $Part[ 'ArrayKey' ] );
			}

			$ChildDto = $Parent->getChild( $Part[ 'Element' ] );

			if( $ChildDto === null )
			{
				$ChildDto = new Dto;
				$Parent->addChild( $ChildDto );
			}

			$ChildParameter = $ChildDto->getParameter( $Part[ 'Name' ] );

			if( !$ChildParameter )
			{
				$Template = $this->getParameterByKey( $Part[ 'ChildKey' ] );

				$ChildParameter = ( clone $Template )->setName( $Part[ 'Name' ] );
				$ChildDto->setParameter( $ChildParameter->getName(), $ChildParameter );
			}

			$Parent = $ChildParameter;
		}

		$Parent->setValue( $Value );
	}
}
