<?php

namespace Neuron\Dto;

use Neuron\Core\Exceptions\Base;

/**
 *
 */

class Validation extends Base
{
	private array $errors;

	public function __construct( string $name, array $errors )
	{
		parent::__construct( "Validation failed for $name" );
		$this->errors = $errors;
	}

	/**
	 * @return array
	 */

	public function getErrors(): array
	{
		return $this->errors;
	}
}
