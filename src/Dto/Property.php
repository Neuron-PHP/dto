<?php
namespace Neuron\Dto;

use Neuron\Validation;
use Neuron\Core\Exceptions;
use Neuron\Data\Object\NumericRange;
use Neuron\Dto\Compound\ICompound;

class Property
{
	private ICompound $_Parent;
	private array	$_Errors;
	private string	$_Name;
	private bool	$_Required;
	private string	$_Type;
	private mixed	$_Value = null;
	private array	$_TypeValidators;
	private Validation\Collection $_Validators;

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
			'array'					=> new Validation\IsObject(),
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
			'uuid'					=> new Validation\IsUuid(),
			'us_phone_number'		=> new Validation\IsPhoneNumber(),
			'intl_phone_number'	=> new Validation\IsPhoneNumber( Validation\IsPhoneNumber::INTERNATIONAL )
		];

		$this->_Validators = new Validation\Collection();
	}

	public function getParent(): ICompound
	{
		return $this->_Parent;
	}

	public function setParent( ICompound $Parent ): Property
	{
		$this->_Parent = $Parent;
		return $this;
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
	 * @return Property
	 */

	public function setName( string $Name ): Property
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
	 * @return Property
	 */

	public function setRequired( bool $Required ): Property
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
	 * @return Property
	 * @throws \Exception
	 */

	public function setType( string $Type ): Property
	{
		$this->_Type = $Type;

		if( !array_key_exists( $this->getType(), $this->_TypeValidators ) )
		{
			throw new \Exception($this->getName().": Invalid type '{$Type}." );
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

	public function setLengthRange( int $Min, int $Max ): Property
	{
		$this->_Validators->remove( 'length' );

		$this->_Validators->add( 'length', new Validation\IsStringLength( $Min, $Max ) );

		return $this;
	}

	/**
	 * @param mixed $Min
	 * @param mixed $Max
	 * @return $this
	 */

	public function setValueRange( mixed $Min, mixed $Max ): Property
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
	 * @return Property
	 */

	public function setPattern( string $Pattern ): Property
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
	 * @return Property
	 */

	public function setValue( mixed $Value ): Property
	{
		$this->_Value = $Value;

		return $this;
	}

	/**
	 * @throws Exceptions\Validation
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
			}
		}

		if( count( $this->_Errors ) )
		{
			throw new Exceptions\Validation( $this->_Name, $this->_Errors );
		}
	}

	/**
	 * @return bool
	 */

	private function validateRequired(): bool
	{
		if( $this->_Required )
		{
			if( $this->getType() == 'array' && !count( $this->getValue()->getChildren() ) )
			{
				$this->_Errors[] = $this->_Name . ": array item is required.";
				return false;
			}

			if( !$this->_Value )
			{
				$this->_Errors[] = $this->_Name.": value is required.";
				return false;
			}
		}

		return true;
	}

	/**
	 * @return string
	 */

	public function getAsJson(): string
	{
		if( $this->getType() == 'array' )
		{
			return $this->getArrayAsJson();
		}

		if( $this->getType() == 'object' )
		{
			return $this->getDtoAsJson();
		}

		return "\"{$this->getName()}\":\"{$this->getValue()}\"";
	}

	/**
	 * @return string
	 * @throws \Exception
	 */

	protected function getArrayAsJson(): string
	{
		/** @var Collection $Collection */
		$Collection = $this->getValue();
		return "\"{$this->getName()}\":{$Collection->getAsJson()}";
	}

	/**
	 * @return string
	 */

	protected function getDtoAsJson(): string
	{
		/** @var Dto $Dto */
		$Dto = $this->getValue();
		$Json = $Dto->getAsJson();

		return "\"{$this->getName()}\":{$Json}";
	}
}
