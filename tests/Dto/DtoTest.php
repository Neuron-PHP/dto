<?php
namespace Test\Dto;

use Neuron\Dto\Dto;
use Neuron\Dto\DtoFactory;
use PHPUnit\Framework\TestCase;

class DtoTest extends TestCase
{
	public Dto $Dto;

	public function setUp(): void
	{
		$Factory = new DtoFactory( 'examples/test.yaml' );
		$this->Dto = $Factory->create();
	}

	public function testDto()
	{
		$this->assertIsArray(
			$this->Dto->getParameters()
		);

		$this->assertArrayHasKey(
			'username',
			$this->Dto->getParameters()
		);

		$this->assertArrayHasKey(
			'password',
			$this->Dto->getParameters()
		);

		$this->assertArrayHasKey(
			'username',
			$this->Dto->getParameters()
		);

		$Address = $this->Dto->getParameter( 'address' )->getValue();

		$this->assertNotNull( $Address );

		$this->assertArrayHasKey(
			'street',
			$Address->getParameters()
		);
	}
}
