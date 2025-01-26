<?php

namespace Dto;

use Neuron\Dto\MapperFactory;
use PHPUnit\Framework\TestCase;

class MapperFactoryTest extends TestCase
{
	public function testCreate()
	{
		$MapperFactory = new MapperFactory( 'examples/test-json-map.yaml' );

		$Mapper = $MapperFactory->create();

		$this->assertIsObject( $Mapper );

		$this->assertEquals(
			'test.username',
			$Mapper->getAlias( 'user.name' )
		);
	}

	public function testGetFileName()
	{
		$MapperFactory = new MapperFactory( 'example' );

		$this->assertEquals( 'example', $MapperFactory->getFileName() );
	}
}
