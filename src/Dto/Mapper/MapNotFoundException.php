<?php

namespace Neuron\Dto\Mapper;

/**
 *
 */

class MapNotFoundException extends \Exception
{
	public function __construct( string $Name )
	{
		parent::__construct( "Missing map to: $Name" );
	}
}
