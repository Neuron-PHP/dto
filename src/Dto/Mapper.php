<?php

namespace Neuron\Dto;

use Neuron\Log\Log;

class Mapper
{
	private string $_Name;
	private array $_Aliases;
	private array $_Fields;
	private array $_Parameters;

	public function __construct()
	{
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
	 * @param string $PropertyName
	 * @param string $AliasName
	 * @return $this
	 */
	public function setAlias( string $PropertyName, string $AliasName ): Mapper
	{
		$this->_Aliases[ $PropertyName ] = $AliasName;

		return $this;
	}

	/**
	 * @param string $PropertyName
	 * @return string|null
	 */
	public function getAlias( string $PropertyName ): ?string
	{
		return $this->_Aliases[ $PropertyName ] ?? null;
	}

	/**
	 * @param Dto $Dto
	 * @param array $Data
	 * @return Dto
	 * @throws ValidationException
	 */
	public function map( Dto $Dto, array $Data ) : Dto
	{
		$Dto->clearErrors();

		return $this->mapDto( $Dto, $Data );
	}

	/**
	 * Turns a nested dictionary into a flat schema.
	 * The result is a one level dictionary where each field is keyed such that
	 * level1.level2.level3.parameter = value.
	 *
	 * @param array $Array
	 * @param string|null $MasterKey
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
	 * Turns a nested dictionary into a flat schema.
	 * The result is a one level dictionary where each field is keyed such that
	 * level1.level2.level3.parameter = value.
	 *
	 * @param Dto $Dto
	 * @param string|null $MasterKey
	 * @return void
	 */
	public function flattenParameters( Dto $Dto, ?string $MasterKey = null ): void
	{
		if( $MasterKey )
		{
			$MasterKey .= '.'.$Dto->getName();
		}
		else
		{
			$MasterKey = $Dto->getName();
		}

		$Parameters = $Dto->getParameters();

		/** @var Parameter $Parameter */
		foreach( $Parameters as $Parameter )
		{
			$Key = $MasterKey.'.'.$Parameter->getName();

			if( $Parameter->getType() == 'object' )
			{
				$this->flattenParameters( $Parameter->getValue(), $MasterKey );
				$MasterKey = null;
			}
			else
			{
				$this->_Parameters[ $Key ] = $Parameter;
			}
		}
	}

	/**
	 * @throws ValidationException
	 */
	private function mapDto( Dto $Dto, array $Data ): Dto
	{
		$this->flattenParameters( $Dto );
		$this->flattenFields( $Data );

		foreach( $this->_Parameters as $Key => $Parameter )
		{
			$FieldName = $this->getAlias( $Key );

			$Value = $this->_Fields[ $FieldName ] ?? null;

			if( $Value )
			{
				$Parameter->setValue( $Value );
			}
		}

		$Dto->validate();

		return $Dto;


		foreach( $Dto->getParameters() as $Parameter )
		{
			if( !isset( $Data[ $Parameter->getName() ] )  )
			{
				$Dto->validateParameter( $Parameter );
				continue;
			}

			if( $Parameter->getType() === 'object' )
			{
				try
				{
					$this->mapDto( $Parameter->getValue(), $Data[ $Parameter->getName() ] );
					$Parameter->getValue()->setParent( $Dto );
				}
				catch( ValidationException $Exception )
				{
					Log::warning( $Exception->getMessage() );
					$Dto->addErrors( $Exception->getErrors() );
				}
				continue;
			}

			$Parameter->setValue( $Data[ $Parameter->getName() ] );

			$Dto->validateParameter( $Parameter );
		}

		if( !empty( $Dto->getErrors() ) )
		{
			throw new ValidationException( $Dto->getName(), $Dto->getErrors() );
		}

		return $Dto;
	}
}
