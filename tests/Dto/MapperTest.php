<?php
namespace Dto;

use Neuron\Dto\Factory;
use Neuron\Dto\Mapper;
use Neuron\Dto\Mapper\Dynamic;
use Neuron\Dto\Mapper\MapNotFoundException;
use Neuron\Dto\ValidationException;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
	public array      $SuccessPayload;
	public Mapper\Factory $MapperFactory;
	public Factory $DtoFactory;

	protected function setUp(): void
	{
		$this->MapperFactory	= new Mapper\Factory( 'examples/test-json-map.yaml' );
		$this->DtoFactory		= new Factory( 'examples/test.yaml' );

		$this->SuccessPayload = [
			'unused' => [
				[
					'one' => 1,
				],
				[
					'two' => 2,
				]
			],
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
								'leather',
								'boot',
								'smelly'
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
				],
				'nested' => [
					[
						'level2' => '2'
					]
				],
				"test_username" => 'test_username',
				"test_email" => 'test_email'
			]
		];

		parent::setUp();
	}

	public function testFlattenFields()
	{
		$Mapper = new Dynamic();

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

	public function testFlattenProperties()
	{
		$Mapper	= $this->MapperFactory->create();
		$Dto	= $this->DtoFactory->create();

		$Dto->address->street = 'test';

		$Mapper->flattenProperties( $Dto );

		$this->assertEquals(
			$Mapper->getProperties()[ 'test.address.street' ]->getValue(),
			'test'
		);
	}

	public function testGetArrayElement()
	{
		$Mapper	= $this->MapperFactory->create();
		$Dto	= $this->DtoFactory->create();

		$Mapper->flattenProperties( $Dto );
		$Array = $Mapper->getArrayPath( 'user.inventory.0.attributes.1' );
		$Array = $Mapper->getArrayPath( 'user.inventory.0.name' );

		$this->assertIsArray( $Array );
	}

	public function testSetName()
	{
		$Mapper = new Dynamic();

		$Mapper->setName( 'test' );

		$this->assertEquals( 'test', $Mapper->getName() );
	}

	public function testSetAlias()
	{
		$Mapper = new Dynamic();

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

	public function testStrictErrors()
	{
		$Mapper = $this->MapperFactory->create();

		$Dto = $this->DtoFactory->create();

		$Mapper->map( $Dto, $this->SuccessPayload );

		$Mapper->setStrictErrors( true );

		$this->assertEquals( true, $Mapper->isStrictErrors() );
	}


	public function testArrayRequired()
	{
		$Payload = [
			'unused' => [
				[
					'one' => 1,
				],
				[
					'two' => 2,
				]
			],
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
				'inventory' => [],
				'nested' => [
					[
						'level2' => '2'
					]
				]
			]
		];

		$Mapper = $this->MapperFactory->create();

		$Dto = $this->DtoFactory->create();

		$Errors = [];

		try
		{
			$Mapper->map( $Dto, $Payload );
		}
		catch( ValidationException $Exception )
		{
		}

		$this->assertNotEmpty( $Dto->getErrors() );
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
			$Dto->getProperty( 'username' )->getValue()
		);

		$this->assertEquals(
			'testtest',
			$Dto->getProperty( 'password' )->getValue()
		);

		$this->assertEquals(
			1,
			$Dto->getProperty( 'inventory' )
				->getValue()
				->getChild( 0 )
				->getProperty( 'amount' )
				->getValue()
		);

		$this->assertEquals(
			2,
			$Dto->getProperty( 'inventory' )
				->getValue()
				->getChild( 1 )
				->getProperty( 'amount' )
				->getValue()
		);

		$this->assertEquals(
			3,
			$Dto->getProperty( 'inventory' )
				->getValue()
				->getChild( 2 )
				->getProperty( 'amount' )
				->getValue()
		);

		$this->assertEquals(
			3,
			count( $Dto->getProperty( 'inventory' )->getValue()->getChildren() )
		);

		$this->assertIsArray( $Dto->inventory );

		$this->assertEquals(
			3,
			count(
				$Dto->getProperty( 'inventory' )
					->getValue()
					->getChild( 0 )
					->getProperty( 'attributes' )
					->getValue()
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

		$this->assertEquals(
			'boot',
			$Dto->inventory[ 0 ]->attributes[ 1 ]
		);

	}

	public function testMapFail()
	{
		$Mapper = new Dynamic();

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

	public function testGetAsJson()
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

		$json = $Dto->getAsJson();

		json_decode( $json );

		$this->assertTrue( json_last_error() == JSON_ERROR_NONE );
	}
}
