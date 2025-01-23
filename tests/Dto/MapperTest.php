<?php

namespace Dto;

use Neuron\Dto\DtoFactory;
use Neuron\Dto\Mapper;
use Neuron\Dto\MapperFactory;
use Neuron\Dto\ValidationException;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{

	public function testFlattenFields()
	{
		$MapperFactory = new MapperFactory( 'examples/test-json.map.yaml' );
		$Mapper = new Mapper();

		$Payload = [
			'user' => [
				'name' => 'test',
				'password' => 'testtest',
				'age'      => 40,
				'birthday' => '1978-01-01',
				'address'  => [
					'street' => '13 Mocking',
					'city'   => 'Mockingbird Heights',
					'state'  => 'CA',
					'zip'    => '90210'
				]
			]
		];

		$Mapper->flattenFields( $Payload );

		$this->assertEquals(
			$Mapper->getFields()[ 'user.name' ],
			'test'
		);

		$this->assertEquals(
			$Mapper->getFields()[ 'user.address.street' ],
			'13 Mocking'
		);
	}

	public function testFlattenParameters()
	{
		$MapperFactory = new MapperFactory( 'examples/test-json-map.yaml' );
		$Mapper = $MapperFactory->create();

		$DtoFactory = new DtoFactory( 'examples/test.yaml' );
		$Dto = $DtoFactory->create();

		$Dto->address->street = 'test';

		$Mapper->flattenParameters( $Dto );

		$this->assertEquals(
			$Mapper->getParameters()[ 'test.address.street' ]->getValue(),
			'test'
		);
	}

	public function testSetName()
	{
		$Mapper = new Mapper();

		$Mapper->setName( 'test' );

		$this->assertEquals( 'test', $Mapper->getName() );
	}

	public function testSetAlias()
	{
		$Mapper = new Mapper();

		$Mapper->setAlias( 'test', 'alias' );

		$this->assertEquals( 'alias', $Mapper->getAlias( 'test' ) );

	}

	public function testMapSuccess()
	{
		$MapperFactory = new MapperFactory( 'examples/test-json-map.yaml' );
		$Mapper = $MapperFactory->create();

		$Factory = new DtoFactory( 'examples/test.yaml' );

		$Dto = $Factory->create();

		$Payload = [
			'user' => [
				'name' => 'test',
				'password' => 'testtest',
				'age'      => 40,
				'birthday' => '1978-01-01',
				'address'  => [
					'street' => '13 Mocking',
					'city'   => 'Mockingbird Heights',
					'state'  => 'CA',
					'zip'    => '90210'
				]
			]
		];


		$Errors = [];

		try
		{
			$Mapper->map( $Dto, $Payload );
		}
		catch( ValidationException $Exception )
		{
			$Errors = $Exception->getErrors();
		}

		$this->assertEmpty( $Errors );

		$this->assertEquals(
			'test',
			$Dto->getParameter( 'username' )->getValue()
		);

		$this->assertEquals(
			'testtest',
			$Dto->getParameter( 'password' )->getValue()
		);
	}

	public function testMapFail()
	{
		$Mapper = new Mapper();

		$Factory = new DtoFactory( 'examples/test.yaml' );

		$Dto = $Factory->create();

		$Payload = [
			'username' => 'test',
			'password' => 'testtest',
			'age'      => 42,
			'birthday' => '1978-01-01',
			'address'  => [
				'street' => '13 Mockingbird Lane.',
				'city'   => 'Mockingbird Heights',
				'state'  => 'CA'
			]
		];

		try
		{
			$Mapper->map( $Dto, $Payload );
		}
		catch( ValidationException $Exception )
		{}

		$this->assertNotEmpty( $Dto->getErrors() );

		$Errors = $Dto->getErrors();

		$this->assertEquals(
			"test.username: value is required.",
			$Errors[ 0 ]
		);
	}

}
