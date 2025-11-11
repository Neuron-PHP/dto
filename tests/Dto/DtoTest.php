<?php
namespace Test\Dto;

use Neuron\Dto\Dto;
use Neuron\Dto\Factory;
use PHPUnit\Framework\TestCase;

class DtoTest extends TestCase
{
	public Dto $Dto;

	public function setUp(): void
	{
		$Factory = new Factory( 'examples/test.yaml' );
		$this->Dto = $Factory->create();
	}

	public function testDto()
	{
		$this->assertIsArray(
			$this->Dto->getProperties()
		);

		$this->assertArrayHasKey(
			'username',
			$this->Dto->getProperties()
		);

		$this->assertArrayHasKey(
			'password',
			$this->Dto->getProperties()
		);

		$this->assertArrayHasKey(
			'username',
			$this->Dto->getProperties()
		);

		$Address = $this->Dto->getProperty( 'address' )->getValue();

		$this->assertNotNull( $Address );

		$this->assertArrayHasKey(
			'street',
			$Address->getProperties()
		);
	}
}
