<?php

namespace Dto;

use Neuron\Dto\Factory;
use PHPUnit\Framework\TestCase;

class DtoFactoryTest extends TestCase
{

	public function test__construct()
	{
		$Factory = new Factory( 'test.yaml' );

		$this->assertEquals(
			'test.yaml',
			$Factory->getFileName()
		);
	}

	public function testCreate()
	{
		$Factory = new Factory( 'examples/test.yaml' );

		$Dto = $Factory->create();

		$this->assertIsObject( $Dto );

		$this->assertEquals(
			'array',
			$Dto->getProperty( 'inventory' )
				 ->getType()
		);

		$this->assertEquals(
			$Dto,
			$Dto->getProperty( 'address' )
				 ->getValue()
				 ->getParent()
		);
	}
}
