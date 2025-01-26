<?php

namespace Dto;

use Neuron\Dto\Dto;
use Neuron\Dto\DtoFactory;
use Neuron\Dto\Mapper;
use Neuron\Dto\MapperFactory;
use Neuron\Dto\MapNotFoundException;
use Neuron\Dto\ValidationException;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
	public array $SuccessPayload;
	public MapperFactory $MapperFactory;
	public DtoFactory $DtoFactory;

	protected function setUp(): void
	{
		$this->MapperFactory	= new MapperFactory( 'examples/test-json-map.yaml' );
		$this->DtoFactory		= new DtoFactory( 'examples/test.yaml' );

		$this->SuccessPayload = [
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
				],
				'inventory' => [
					[
						'name' => 'shoes',
						'count' => 1,
						'attributes' => [
							[
								'name' => 'leather',
							],
							[
								'name' => 'boot',
							],
							[
								'name' =>'smelly'
							]
						]
					],
					[
						'name' => 'jackets',
						'count' => 2
					],
					[
						'name' => 'pants',
						'count' => 3
					]
				]
			]
		];

		parent::setUp();
	}

	public function testFlattenFields()
	{
		$Mapper = new Mapper();

		$Mapper->flattenFields( $this->SuccessPayload );

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
		$Mapper	= $this->MapperFactory->create();
		$Dto		= $this->DtoFactory->create();

		$Dto->address->street = 'test';

		$Mapper->flattenParameters( $Dto );

		$this->assertEquals(
			$Mapper->getParameters()[ 'test.address.street' ]->getValue(),
			'test'
		);
	}

	public function testGetArrayElement()
	{
		$Mapper	= $this->MapperFactory->create();
		$Dto	= $this->DtoFactory->create();

		$Mapper->flattenParameters( $Dto );
		$Array = $Mapper->getArrayPath( 'user.inventory.0.attributes.1' );
		$Array = $Mapper->getArrayPath( 'user.inventory.0.name' );

		$this->assertIsArray( $Array );
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

		$this->assertEquals( 'test', $Mapper->getAlias( 'alias' ) );
	}

	public function testStrictMapping()
	{
		$Mapper = $this->MapperFactory->create();

		$Dto = $this->DtoFactory->create();

		$Errors = [];

		$Pass = false;
		$Mapper->setStrictMapping( true );

		try
		{
			$Mapper->map( $Dto, $this->SuccessPayload );
		}
		catch( MapNotFoundException $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testArrayAccess()
	{
		$Mapper = $this->MapperFactory->create();

		$Dto = $this->DtoFactory->create();

		$Errors = [];

		try
		{
			$Mapper->map( $Dto, $this->SuccessPayload );
		}
		catch( ValidationException $Exception )
		{
			$Errors = $Exception->getErrors();
		}

		$Pass = false;

		try
		{
			$Dto->getParameter( 'username' )->addChild( new Dto() );
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );

		$Pass = false;

		try
		{
			$Dto->getParameter( 'username' )->getChild( 1 );
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );

		$Pass = false;

		try
		{
			$Dto->getParameter( 'username' )->getChildren();
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testStrictErrors()
	{
		$Mapper = $this->MapperFactory->create();

		$Dto = $this->DtoFactory->create();

		$Mapper->map( $Dto, $this->SuccessPayload );

		$Mapper->setStrictErrors( true );

		$this->assertEquals( true, $Mapper->isStrictErrors() );
	}

	public function testMapSuccess()
	{
		$Mapper = $this->MapperFactory->create();

		$Dto = $this->DtoFactory->create();

		$Errors = [];

		try
		{
			$Mapper->map( $Dto, $this->SuccessPayload );
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

		$this->assertEquals(
			1,
			$Dto->getParameter( 'inventory' )
				 ->getChild( 0 )
				 ->getParameter( 'amount' )
				 ->getValue()
		);

		$this->assertEquals(
			2,
			$Dto->getParameter( 'inventory' )
				 ->getChild( 1 )
				 ->getParameter( 'amount' )
				 ->getValue()
		);

		$this->assertEquals(
			3,
			$Dto->getParameter( 'inventory' )
				 ->getChild( 2 )
				 ->getParameter( 'amount' )
				 ->getValue()
		);

		$this->assertEquals(
			3,
			count( $Dto->getParameter( 'inventory' )->getChildren() )
		);

		$this->assertIsArray( $Dto->inventory );

		$this->assertEquals(
			3,
			count(
				$Dto->getParameter( 'inventory' )
					->getChild( 0 )
					->getParameter( 'attributes' )
					->getChildren()
			)
		);

		$this->assertEquals(
			3,
			count( $Dto->inventory[ 0 ]->attributes )
		);

		$Test = $Dto->inventory[ 2 ]->amount;

		$this->assertEquals(
			3,
			$Dto->inventory[ 2 ]->amount
		);
	}

	public function testMapFail()
	{
		$Mapper = new Mapper();

		$Dto = $this->DtoFactory->create();

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
		{
		}

		$this->assertNotEmpty( $Dto->getErrors() );

		$Errors = $Dto->getErrors();

		$this->assertEquals(
			"test.username: value is required.",
			$Errors[ 0 ]
		);
	}
}
