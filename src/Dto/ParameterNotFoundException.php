<?php

namespace Neuron\Dto;

class ParameterNotFoundException extends \Exception
{
	public function __construct( string $Name )
	{
		parent::__construct( "Missing map to: $Name" );
	}
}
