<?php


use Neuron\Dto\Dto;
use Neuron\Dto\DtoFactory;
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
	public Dto $Dto;

	public function setUp(): void
	{
		$Factory = new DtoFactory( 'examples/test.yaml' );
		$this->Dto = $Factory->create();
	}

	public function testSetParameter()
	{
		$this->Dto->age = 40;

		$this->assertEquals(
			40,
			$this->Dto->age
		);

		$this->Dto->address->street;
	}

	public function testGetNestedParameter()
	{
		$this->Dto->age = 40;

		$this->assertEquals(
			40,
			$this->Dto->age
		);

		$this->Dto->address->street = '13 Mocking';
	}

	public function testGetParamNotFound()
	{
		$Pass = false;

		try
		{
			$this->Dto->not_found;
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testSetParamNotFound()
	{
		$Pass = false;

		try
		{
			$this->Dto->not_found = 1;
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testMinRangeError()
	{
		$Pass = false;

		try
		{
			$this->Dto->age = 1;
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testMaxRangeError()
	{
		$Pass = false;

		try
		{
			$this->Dto->age = 100;
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testMinLengthError()
	{
		$Pass = false;

		try
		{
			$this->Dto->password = ' ';
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testMaxLengthError()
	{
		$Pass = false;

		try
		{
			$this->Dto->password = '01234567890';
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testTypeErrorString()
	{
		$Pass = false;

		try
		{
			$this->Dto->password = 1;
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testTypeErrorInt()
	{
		$Pass = false;

		try
		{
			$this->Dto->age = '1';
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testTypeErrorBadType()
	{
		$Pass = false;

		try
		{
			$this->Dto->getProperty( 'age' )->setType( 'monkey' );
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testValidatePattern()
	{
		$this->Dto->getProperty( 'username' )->setPattern( '/^[a-zA-Z0-9]+$/' );

		$this->Dto->username = 'testname';

		$Pass = false;
		try
		{
			$this->Dto->username = 'testname!';
		}
		catch( \Exception $Exception )
		{
			$Pass = true;
		}

		$this->assertTrue( $Pass );
	}

	public function testRequired()
	{
		$this->Dto->getProperty( 'age' )->setRequired( true );

		$this->assertTrue( $this->Dto->getProperty( 'age' )->isRequired() );
	}
}
