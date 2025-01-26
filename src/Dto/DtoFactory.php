<?php

namespace Neuron\Dto;

use Exception;
use Symfony\Component\Yaml\Yaml;

class DtoFactory
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
	public function getFileName() : string
	{
		return $this->_FileName;
	}

	/**
	 * @throws Exception
	 */
	public function create() : Dto
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
			$Parameter = new Parameter();
			$Parameter->setName( $Name );

			if( isset( $ParamData[ 'required' ] ) )
			{
				$Parameter->setRequired( $ParamData[ 'required' ] );
			}

			$Parameter->setType( $ParamData[ 'type' ] );

			if( $Parameter->getType() === 'object' || $Parameter->getType() === 'array' )
			{
				$ParamDto = $this->createDto( $Name, $ParamData[ 'properties' ] );
				$ParamDto->setParent( $Dto );
				$Parameter->setValue( $ParamDto );
			}

			if( isset( $ParamData[ 'length' ] ) )
			{
				$Min = $Max = 0;

				if( isset( $ParamData[ 'length' ][ 'min' ] ) )
				{
					$Min =  $ParamData[ 'length' ][ 'min' ];
				}

				if( isset( $ParamData[ 'length' ][ 'max' ] ) )
				{
					$Max =  $ParamData[ 'length' ][ 'max' ];
				}

				$Parameter->setLengthRange( $Min, $Max );
			}

			if( isset( $ParamData[ 'range' ] ) )
			{
				$Min = $Max = 0;

				if( isset( $ParamData[ 'range' ][ 'min' ] ) )
				{
					$Min = $ParamData[ 'range' ][ 'min' ];
				}

				if( isset( $ParamData[ 'range' ][ 'max' ] ) )
				{
					$Max = $ParamData[ 'range' ][ 'max' ];
				}

				$Parameter->setValueRange( $Min, $Max );
			}

			if( isset( $ParamData[ 'pattern' ] ) )
			{
				$Parameter->setPattern( $ParamData[ 'pattern' ] );
			}

			$Dto->setParameter( $Parameter->getName(), $Parameter );
		}

		return $Dto;
	}
}
