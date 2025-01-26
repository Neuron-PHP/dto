<?php

namespace Neuron\Dto;

use Symfony\Component\Yaml\Yaml;

class MapperFactory
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
	 * @return Mapper
	 */

	public function create() : Mapper
	{
		$Name = pathinfo( $this->_FileName )[ 'filename' ];
		$Data = Yaml::parseFile( $this->_FileName );

		return $this->createMapper( $Name, $Data[ 'map' ] );
	}

	/**
	 * @param string $Name
	 * @param array $Data
	 * @return Mapper
	 */

	protected function createMapper( string $Name, array $Data ) : Mapper
	{
		$Mapper = new Mapper();
		$Mapper->setName( $Name );

		foreach( $Data as $Key => $Value )
		{
			$Mapper->setAlias( $Key, $Value );
		}

		return $Mapper;
	}
}
