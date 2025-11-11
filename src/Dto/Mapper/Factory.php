<?php

namespace Neuron\Dto\Mapper;

use Symfony\Component\Yaml\Yaml;

class Factory
{
	private ?string $_fileName;

	/**
	 * @param ?string $fileName
	 */

	public function __construct( ?string $fileName = null )
	{
		$this->_fileName = $fileName;
	}

	/**
	 * @return string
	 */

	public function getFileName(): string
	{
		return $this->_fileName;
	}

	/**
	 * @return Dynamic
	 */

	public function create() : Dynamic
	{
		$name = pathinfo( $this->_fileName )[ 'filename' ];
		$data = Yaml::parseFile( $this->_fileName );

		return $this->createMapper( $name, $data[ 'map' ] );
	}

	/**
	 * @param string $name
	 * @param array $data
	 * @return Dynamic
	 */

	protected function createMapper( string $name, array $data ) : Dynamic
	{
		$mapper = new Dynamic();
		$mapper->setName( $name );

		foreach( $data as $key => $value )
		{
			$mapper->setAlias( $value, $key );
		}

		return $mapper;
	}
}
