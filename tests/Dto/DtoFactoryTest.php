<?php

namespace Dto;

use Neuron\Dto\DtoFactory;
use PHPUnit\Framework\TestCase;

class DtoFactoryTest extends TestCase
{

	public function test__construct()
	{
		$Factory = new DtoFactory( 'test.yaml' );

		$this->assertEquals(
			'test.yaml',
			$Factory->getFileName()
		);
	}

	public function testCreate()
	{
		$Factory = new DtoFactory( 'examples/test.yaml' );

		$Dto = $Factory->create();

		$this->assertIsObject( $Dto );

		$this->assertEquals(
			'array',
			$Dto->getParameter( 'inventory' )
				 ->getType()
		);

		$this->assertEquals(
			$Dto,
			$Dto->getParameter( 'address' )
				 ->getValue()
				 ->getParent()
		);
	}
}
