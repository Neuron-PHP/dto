<?php

namespace Neuron\Dto\Mapper;

use Symfony\Component\Yaml\Yaml;

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
	 * @return Dynamic
	 */

	public function create() : Dynamic
	{
		$Name = pathinfo( $this->_FileName )[ 'filename' ];
		$Data = Yaml::parseFile( $this->_FileName );

		return $this->createMapper( $Name, $Data[ 'map' ] );
	}

	/**
	 * @param string $Name
	 * @param array $Data
	 * @return Dynamic
	 */

	protected function createMapper( string $Name, array $Data ) : Dynamic
	{
		$Mapper = new Dynamic();
		$Mapper->setName( $Name );

		foreach( $Data as $Key => $Value )
		{
			$Mapper->setAlias( $Value, $Key );
		}

		return $Mapper;
	}
}
