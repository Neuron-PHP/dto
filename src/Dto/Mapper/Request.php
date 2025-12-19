<?php

namespace Neuron\Dto\Mapper;

use Neuron\Data\Filters\Post;
use Neuron\Dto\Dto;

/**
 * Maps HTTP Request data to DTOs with proper filtering
 *
 * This mapper handles POST data, applying proper sanitization through
 * the filter_input functions before mapping to DTO properties.
 *
 * @package Neuron\Dto\Mapper
 */
class Request implements IMapper
{
	/**
	 * Map filtered POST data to DTO properties
	 *
	 * Iterates through all POST keys and maps them to corresponding
	 * DTO properties if they exist. Uses Post::filterScalar() for
	 * proper input sanitization.
	 *
	 * @param Dto $dto The DTO to populate
	 * @param array $data Optional data array. If empty, uses filtered POST data
	 * @return Dto The populated DTO
	 */
	public function map( Dto $dto, array $data = [] ): Dto
	{
		// If no data provided, get all POST keys
		if( empty( $data ) )
		{
			$data = array_keys( $_POST );
		}

		foreach( $data as $field => $value )
		{
			// If data is just keys (array of strings), fetch filtered values
			if( is_int( $field ) && is_string( $value ) )
			{
				$field = $value;
				$value = Post::filterScalar( $field );
			}

			// Get the DTO property
			$property = $dto->getProperty( $field );

			// Skip if property doesn't exist in DTO
			if( !$property )
			{
				continue;
			}

			// Set the value (validation happens per-property)
			try
			{
				$dto->$field = $value;
			}
			catch( \Neuron\Core\Exceptions\Validation $e )
			{
				// Property-level validation errors are collected
				// They'll be returned when validate() is called on the DTO
			}
		}

		return $dto;
	}

	/**
	 * Map filtered POST array data to DTO
	 *
	 * Alternative method that uses Post::filterArray() for batch filtering.
	 *
	 * @param Dto $dto The DTO to populate
	 * @param array $fields Field names to map (defaults to all POST fields)
	 * @return Dto The populated DTO
	 */
	public function mapFiltered( Dto $dto, array $fields = [] ): Dto
	{
		// Build filter array for filter_input_array
		$filterDef = [];

		if( empty( $fields ) )
		{
			// Use all POST keys
			$fields = array_keys( $_POST );
		}

		foreach( $fields as $field )
		{
			if( $dto->getProperty( $field ) )
			{
				$filterDef[ $field ] = FILTER_DEFAULT;
			}
		}

		// Filter all POST data at once
		$filtered = Post::filterArray( $filterDef );

		if( $filtered === false || $filtered === null )
		{
			return $dto;
		}

		// Map filtered data to DTO
		foreach( $filtered as $field => $value )
		{
			try
			{
				$dto->$field = $value;
			}
			catch( \Neuron\Core\Exceptions\Validation $e )
			{
				// Validation errors collected in DTO
			}
		}

		return $dto;
	}
}