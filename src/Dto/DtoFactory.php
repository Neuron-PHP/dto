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

		foreach( $Data as $Name => $Parameter )
		{
			$P = new Parameter();
			$P->setName( $Name );

			if( isset( $Parameter[ 'required' ] ) )
			{
				$P->setRequired( $Parameter[ 'required' ] );
			}

			$P->setType( $Parameter[ 'type' ] );

			if( $P->getType() === 'object' || $P->getType() === 'array' )
			{
				$ParamDto = $this->createDto( $Name, $Parameter[ 'properties' ] );
				$ParamDto->setParent( $Dto );
				$P->setValue( $ParamDto );
			}

			if( isset( $Parameter[ 'length' ] ) )
			{
				$Min = $Max = 0;

				if( isset( $Parameter[ 'length' ][ 'min' ] ) )
				{
					$Min =  $Parameter[ 'length' ][ 'min' ];
				}

				if( isset( $Parameter[ 'length' ][ 'max' ] ) )
				{
					$Max =  $Parameter[ 'length' ][ 'max' ];
				}

				$P->setLengthRange( $Min, $Max );
			}

			if( isset( $Parameter[ 'range' ] ) )
			{
				$Min = $Max = 0;

				if( isset( $Parameter[ 'range' ][ 'min' ] ) )
				{
					$Min = $Parameter[ 'range' ][ 'min' ];
				}

				if( isset( $Parameter[ 'range' ][ 'max' ] ) )
				{
					$Max = $Parameter[ 'range' ][ 'max' ];
				}

				$P->setValueRange( $Min, $Max );
			}

			if( isset( $Parameter[ 'pattern' ] ) )
			{
				$P->setPattern( $Parameter[ 'pattern' ] );
			}

			$Dto->setParameter( $P->getName(), $P );
		}

		return $Dto;
	}
}
