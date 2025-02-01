<?php

namespace Neuron\Dto\Mapper;

use Neuron\Dto\Dto;
use Neuron\Dto\MapNotFoundException;
use Neuron\Dto\ValidationException;

class Generic implements IMapper
{
	/**
	 * @param Dto $Dto
	 * @param array $Data
	 * @return Dto
	 */

	public function map( Dto $Dto, array $Data ): Dto
	{
		// TODO: Implement map() method.
	}
}
