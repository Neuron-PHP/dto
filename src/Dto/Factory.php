<?php

namespace Neuron\Dto;

use Exception;
use Neuron\Dto\Compound\ICompound;
use Symfony\Component\Yaml\Yaml;

/**
 * Factory class for creating Data Transfer Objects (DTOs) from YAML configuration files.
 * 
 * This factory reads YAML files containing DTO definitions and creates corresponding
 * Dto objects with properties, validation rules, and nested structures.
 * 
 * @package Neuron\Dto
 */
class Factory
{
	private string $_FileName;

	/**
	 * @param string $FileName
	 */

	public function __construct( string $FileName )
	{
		$this->_FileName = $FileName;
	}

	/**
	 * @return string
	 */

	public function getFileName(): string
	{
		return $this->_FileName;
	}

	/**
	 * @throws Exception
	 */

	public function create(): Dto
	{
		$Name = pathinfo( $this->_FileName )[ 'filename' ];
		$Data = Yaml::parseFile( $this->_FileName );

		return $this->createDto( $Name, $Data[ 'dto' ] );
	}

	/**
	 * @param string $Name
	 * @param array $Data
	 * @return Dto
	 * @throws Exception
	 */

	protected function createDto( string $Name, array $Data ) : Dto
	{
		$Dto = new Dto();

		$Dto->setName( $Name );

		foreach( $Data as $Name => $ParamData )
		{
			$Property = $this->createProperty( $Name, $ParamData, $Dto );
			$Dto->setProperty( $Property->getName(), $Property  );
		}

		return $Dto;
	}

	/**
	 * @param string $Name
	 * @param array $Data
	 * @return Collection
	 * @throws Validation
	 */

	protected function createArray( string $Name, array $Data ): Collection
	{
		$Collection = new Collection();

		$Collection->setName( $Name );

		$Collection->setItemTemplate( $this->createProperty( 'item', $Data, $Collection ) );

		return $Collection;
	}

	/**
	 * @param int|string $Name
	 * @param array $PropertyData
	 * @param Dto $Parent
	 * @return Property
	 * @throws Validation
	 */

	protected function createProperty( int|string $Name, array $PropertyData, ICompound $Parent ): Property
	{
		$Property = new Property();
		$Property->setName( $Name );
		$Property->setParent( $Parent );

		if( isset( $PropertyData[ 'required' ] ) )
		{
			$Property->setRequired( $PropertyData[ 'required' ] );
		}

		$Property->setType( $PropertyData[ 'type' ] );

		if( $Property->getType() === 'object' )
		{
			$ParamDto = $this->createDto( $Name, $PropertyData[ 'properties' ] );
			$ParamDto->setParent( $Parent );
			$Property->setValue( $ParamDto );
		}

		if( $Property->getType() === 'array' )
		{
			$ParamDto = $this->createArray( $Name, $PropertyData[ 'items' ] );
			if( isset( $PropertyData[ 'max' ] ) )
			{
				$Max = $PropertyData[ 'max' ];
				$ParamDto->setRange( 0, $Max );
			}

			$ParamDto->setParent( $Parent );
			$Property->setValue( $ParamDto );
		}

		if( isset( $PropertyData[ 'length' ] ) )
		{
			$this->setLengthRange( $PropertyData, $Property );
		}

		if( isset( $PropertyData[ 'range' ] ) )
		{
			$this->setValueRange( $PropertyData, $Property );
		}

		if( isset( $PropertyData[ 'pattern' ] ) )
		{
			$Property->setPattern( $PropertyData[ 'pattern' ] );
		}

		return $Property;
	}

	/**
	 * @param array $PropertyData
	 * @param Property $Property
	 * @return Property
	 */

	protected function setLengthRange( array $PropertyData, Property $Property ): Property
	{
		$Min = $Max = 0;

		if( isset( $PropertyData[ 'length' ][ 'min' ] ) )
		{
			$Min = $PropertyData[ 'length' ][ 'min' ];
		}

		if( isset( $PropertyData[ 'length' ][ 'max' ] ) )
		{
			$Max = $PropertyData[ 'length' ][ 'max' ];
		}

		$Property->setLengthRange( $Min, $Max );

		return $Property;
	}

	/**
	 * @param array $PropertyData
	 * @param Property $Property
	 * @return mixed
	 */

	protected function setValueRange( array $PropertyData, Property $Property ): mixed
	{
		$Min = $Max = 0;

		if( isset( $PropertyData[ 'range' ][ 'min' ] ) )
		{
			$Min = $PropertyData[ 'range' ][ 'min' ];
		}

		if( isset( $PropertyData[ 'range' ][ 'max' ] ) )
		{
			$Max = $PropertyData[ 'range' ][ 'max' ];
		}

		$Property->setValueRange( $Min, $Max );

		return $Property;
	}
}
