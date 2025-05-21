<?php

namespace Neuron\Dto\Mapper;

use Neuron\Core\Exceptions\MapNotFound;
use Neuron\Dto\Dto;
use Neuron\Dto\Validation;

interface IMapper
{
	/**
	 * Assign data to a dto
	 *
	 * @param Dto $Dto
	 * @param array $Data
	 * @return Dto
	 * @throws Validation|MapNotFound
	 */

	public function map( Dto $Dto, array $Data ): Dto;
}
