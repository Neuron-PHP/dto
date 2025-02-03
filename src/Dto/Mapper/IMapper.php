<?php

namespace Neuron\Dto\Mapper;

use Neuron\Dto\Dto;
use Neuron\Dto\ValidationException;

interface IMapper
{
	/**
	 * Assign data to a dto
	 *
	 * @param Dto $Dto
	 * @param array $Data
	 * @return Dto
	 * @throws ValidationException|MapNotFoundException
	 */
	public function map( Dto $Dto, array $Data ): Dto;
}
