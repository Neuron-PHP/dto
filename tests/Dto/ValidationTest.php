<?php
namespace Dto;

use Neuron\Dto\Validation;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
	public function testConstructorAndGetErrors()
	{
		$errors = [
			'username: value is required',
			'email: invalid format'
		];

		$validation = new Validation( 'TestDto', $errors );

		$this->assertInstanceOf( Validation::class, $validation );
		$this->assertEquals( $errors, $validation->getErrors() );
		$this->assertStringContainsString( 'Validation failed for TestDto', $validation->getMessage() );
	}

	public function testExceptionCanBeThrown()
	{
		$this->expectException( Validation::class );
		$this->expectExceptionMessage( 'Validation failed for UserDto' );

		throw new Validation( 'UserDto', [ 'error1', 'error2' ] );
	}
}
