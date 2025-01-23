<?php
namespace Neuron\Dto;

use Neuron\Data\Object\NumericRange;
use Neuron\Log\Log;
use Neuron\Validation;

class Parameter
{
	private array	$_Errors;
	private string	$_Name;
	private bool	$_Required;
	private string	$_Type;
	private mixed	$_Value = '';
	private array	$_TypeValidators;
	private Validation\Collection $_Validators;

	private array $_Children = [];

	/**
	 *
	 */
	public function __construct()
	{
		$this->_Errors		= [];
		$this->_Name		= '';
		$this->_Required	= false;
		$this->_Type		= '';

		$this->_TypeValidators = [
			'array'					=> new Validation\IsArray(),
			'boolean'				=> new Validation\IsBoolean(),
			'currency'				=> new Validation\IsCurrency(),
			'date'					=> new Validation\IsDate(),
			'date_time'				=> new Validation\IsDateTime(),
			'ein'						=> new Validation\IsEin(),
			'email'					=> new Validation\IsEmail(),
			'float'					=> new Validation\IsFloatingPoint(),
			'integer'				=> new Validation\IsInteger(),
			'ip_address'			=> new Validation\IsIpAddress(),
			'name'					=> new Validation\IsName(),
			'numeric'				=> new Validation\IsNumeric(),
			'object'					=> new Validation\IsObject(),
			'string'					=> new Validation\IsString(),
			'time'					=> new Validation\IsTime(),
			'upc'						=> new Validation\IsUpc(),
			'url'						=> new Validation\IsUrl(),
			'us_phone_number'		=> new Validation\IsPhoneNumber(),
			'intl_phone_number' 	=> new Validation\IsPhoneNumber( Validation\IsPhoneNumber::INTERNATIONAL )
		];

		$this->_Validators = new Validation\Collection();
	}

	/**
	 * @param Dto $Dto
	 * @return void
	 */
	public function addChild( Dto $Dto ) : void
	{
		$this->_Children[] = $Dto;
	}

	/**
	 * @return array
	 */
	public function getChildren() : array
	{
		return $this->_Children;
	}

	/**
	 * @param int $Offset
	 * @return Dto
	 */
	public function getChild( int $Offset ) : Dto
	{
		return $this->_Children[ $Offset ];
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
	 * @return Parameter
	 */
	public function setName( string $Name ): Parameter
	{
		$this->_Name = $Name;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isRequired(): bool
	{
		return $this->_Required;
	}

	/**
	 * @param bool $Required
	 * @return Parameter
	 */
	public function setRequired( bool $Required ): Parameter
	{
		$this->_Required = $Required;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->_Type;
	}

	/**
	 * @param string $Type
	 * @return Parameter
	 * @throws \Exception
	 */
	public function setType( string $Type ): Parameter
	{
		$this->_Type = $Type;

		if( !array_key_exists( $this->getType(), $this->_TypeValidators ) )
		{
			throw new \Exception('Invalid type specified.');
		}

		$this->_Validators->remove( 'type' );

		$this->_Validators->add( 'type', $this->_TypeValidators[ $Type ] );

		return $this;
	}

	/**
	 * @param int $Min
	 * @param int $Max
	 * @return $this
	 */
	public function setLengthRange( int $Min, int $Max ): Parameter
	{
		$this->_Validators->remove( 'length' );

		$this->_Validators->add( 'length', new Validation\IsStringLength( $Min, $Max ) );

		return $this;
	}

	/**
	 * @param int $Min
	 * @param int $Max
	 * @return $this
	 */
	public function setValueRange( int $Min, int $Max ): Parameter
	{
		$this->_Validators->remove( 'range' );

		$Range = new NumericRange( $Min, $Max );
		$Validator = new Validation\IsNumberWithinRange( $Range);

		$this->_Validators->add(
			'range',
			$Validator
		);

		return $this;
	}

	/**
	 * @param string $Pattern
	 * @return Parameter
	 */
	public function setPattern( string $Pattern ): Parameter
	{
		$this->_Validators->remove( 'pattern' );

		$this->_Validators->add( 'pattern', new Validation\IsRegExPattern( $Pattern ) );

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getValue(): mixed
	{
		return $this->_Value;
	}

	/**
	 * @param mixed $Value
	 * @return Parameter
	 */
	public function setValue( mixed $Value ): Parameter
	{
		$this->_Value = $Value;
		return $this;
	}

	/**
	 * @throws ValidationException
	 */
	public function validate(): void
	{
		if( $this->validateRequired() && $this->_Value )
		{
			$this->_Validators->isValid( $this->_Value );
		}

		$Violations = $this->_Validators->getViolations();
		if( count( $Violations ) )
		{
			foreach( $Violations as $Error )
			{
				$Message = "{$this->getName()}: $Error validation failed.";
				$this->_Errors[] = $Message;
				Log::warning( $Message );
			}
		}

		if( count( $this->_Errors ) )
		{
			throw new ValidationException( $this->_Name, $this->_Errors );
		}

	}

	/**
	 * @return bool
	 */
	private function validateRequired(): bool
	{
		if( $this->_Required && !$this->_Value )
		{
			$this->_Errors[] = $this->_Name.": value is required.";
			return false;
		}

		return true;
	}
}
