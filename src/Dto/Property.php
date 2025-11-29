<?php
namespace Neuron\Dto;

use Exception;
use Neuron\Validation;
use Neuron\Core\Exceptions;
use Neuron\Data\Objects\NumericRange;
use Neuron\Dto\Compound\ICompound;

class Property
{
	private ICompound $parent;
	private array	$errors;
	private string	$name;
	private bool	$required;
	private string	$type;
	private mixed	$value = null;
	private array	$typeValidators;
	private Validation\Collection $validators;

	/**
	 *
	 */

	public function __construct()
	{
		$this->errors		= [];
		$this->name		= '';
		$this->required	= false;
		$this->type		= '';

		$this->typeValidators = [
			'array'					=> new Validation\IsObject(),
			'base64'				=> new Validation\IsBase64(),
			'boolean'				=> new Validation\IsBoolean(),
			'currency'				=> new Validation\IsCurrency(),
			'date'					=> new Validation\IsDate(),
			'date_time'				=> new Validation\IsDateTime(),
			'ein'					=> new Validation\IsEin(),
			'email'					=> new Validation\IsEmail(),
			'float'					=> new Validation\IsFloatingPoint(),
			'integer'				=> new Validation\IsInteger(),
			'ip_address'			=> new Validation\IsIpAddress(),
			'name'					=> new Validation\IsName(),
			'numeric'				=> new Validation\IsNumeric(),
			'object'				=> new Validation\IsObject(),
			'string'				=> new Validation\IsString(),
			'time'					=> new Validation\IsTime(),
			'upc'					=> new Validation\IsUpc(),
			'url'					=> new Validation\IsUrl(),
			'uuid'					=> new Validation\IsUuid(),
			'us_phone_number'		=> new Validation\IsPhoneNumber(),
			'intl_phone_number'		=> new Validation\IsPhoneNumber( Validation\IsPhoneNumber::INTERNATIONAL )
		];

		$this->validators = new Validation\Collection();
	}

	public function getParent(): ICompound
	{
		return $this->parent;
	}

	public function setParent( ICompound $parent ): Property
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * @return string
	 */

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 * @return Property
	 */

	public function setName( string $name ): Property
	{
		$this->name = $name;
		return $this;
	}

	/**
	 * @return bool
	 */

	public function isRequired(): bool
	{
		return $this->required;
	}

	/**
	 * @param bool $required
	 * @return Property
	 */

	public function setRequired( bool $required ): Property
	{
		$this->required = $required;
		return $this;
	}

	/**
	 * @return string
	 */

	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 * @return Property
	 * @throws Exception
	 */

	public function setType( string $type ): Property
	{
		$this->type = $type;

		if( !array_key_exists( $this->getType(), $this->typeValidators ) )
		{
			throw new Exception($this->getName().": Invalid type '{$type}." );
		}

		$this->validators->remove( 'type' );

		$this->validators->add( 'type', $this->typeValidators[ $type ] );

		return $this;
	}

	/**
	 * @param int $min
	 * @param int $max
	 * @return $this
	 */

	public function setLengthRange( int $min, int $max ): Property
	{
		$this->validators->remove( 'length' );

		$this->validators->add( 'length', new Validation\IsStringLength( $min, $max ) );

		return $this;
	}

	/**
	 * @param mixed $min
	 * @param mixed $max
	 * @return $this
	 */

	public function setValueRange( mixed $min, mixed $max ): Property
	{
		$this->validators->remove( 'range' );

		$range = new NumericRange( $min, $max );
		$validator = new Validation\IsNumberWithinRange( $range);

		$this->validators->add(
			'range',
			$validator
		);

		return $this;
	}

	/**
	 * @param string $pattern
	 * @return Property
	 */

	public function setPattern( string $pattern ): Property
	{
		$this->validators->remove( 'pattern' );

		$this->validators->add( 'pattern', new Validation\IsRegExPattern( $pattern ) );

		return $this;
	}

	/**
	 * @return mixed
	 */

	public function getValue(): mixed
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return Property
	 */

	public function setValue( mixed $value ): Property
	{
		$this->value = $value;

		return $this;
	}

	/**
	 * @throws Exceptions\Validation
	 */

	public function validate(): void
	{
		if( $this->validateRequired() && $this->value )
		{
			$this->validators->isValid( $this->value );
		}

		$violations = $this->validators->getViolations();

		if( count( $violations ) )
		{
			foreach( $violations as $error )
			{
				$message = "{$this->getName()}: $error validation failed.";
				$this->errors[] = $message;
			}
		}

		if( count( $this->errors ) )
		{
			throw new Exceptions\Validation( $this->name, $this->errors );
		}
	}

	/**
	 * @return bool
	 */

	private function validateRequired(): bool
	{
		if( $this->required )
		{
			if( $this->getType() == 'array' )
			{
				$value = $this->getValue();

				// Check if it's a typed array (Collection) or untyped array (raw array)
				if( $value instanceof Collection )
				{
					if( !count( $value->getChildren() ) )
					{
						$this->errors[] = $this->name . ": array item is required.";
						return false;
					}
				}
				elseif( is_array( $value ) )
				{
					if( empty( $value ) )
					{
						$this->errors[] = $this->name . ": array item is required.";
						return false;
					}
				}
				else
				{
					// Null or other - treat as empty
					$this->errors[] = $this->name . ": array item is required.";
					return false;
				}
			}
			elseif( !$this->value )
			{
				$this->errors[] = $this->name.": value is required.";
				return false;
			}
		}

		return true;
	}

	/**
	 * @return string
	 * @throws Exception
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
	 * @throws Exception
	 */

	protected function getArrayAsJson(): string
	{
		/** @var Collection $collection */
		$collection = $this->getValue();
		return "\"{$this->getName()}\":{$collection->getAsJson()}";
	}

	/**
	 * @return string
	 */

	protected function getDtoAsJson(): string
	{
		/** @var Dto $dto */
		$dto = $this->getValue();
		$json = $dto->getAsJson();

		return "\"{$this->getName()}\":{$json}";
	}
}
