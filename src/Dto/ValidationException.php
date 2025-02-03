<?php

namespace Neuron\Dto;

use Exception;

/**
 *
 */

class ValidationException extends Exception
{
	private array $_Errors;

	public function __construct( string $Name, array $Errors )
	{
		parent::__construct( "Validation failed for $Name" );
		$this->_Errors = $Errors;
	}

	/**
	 * @return array
	 */

	public function getErrors(): array
	{
		return $this->_Errors;
	}
}
