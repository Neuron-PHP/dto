<?php

use Neuron\Dto\Factory;
use Neuron\Core\Exceptions\Validation;
use PHPUnit\Framework\TestCase;

class ImagePropertyTest extends TestCase
{
	/**
	 * Test creating a DTO with an image property.
	 */
	public function testCreateDtoWithImageProperty()
	{
		$properties = [
			'profile_picture' => [
				'type' => 'image',
				'required' => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Valid base64 JPEG image (1x1 pixel)
		$jpegBase64 = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=';

		$dto->profile_picture = $jpegBase64;
		$dto->validate();

		$this->assertEquals( $jpegBase64, $dto->profile_picture );
	}

	/**
	 * Test required image property validation.
	 */
	public function testRequiredImageProperty()
	{
		$properties = [
			'avatar' => [
				'type' => 'image',
				'required' => true
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Should return false when required image is missing
		$result = $dto->validate();
		$this->assertFalse( $result );

		$errors = $dto->getErrors();
		$this->assertNotEmpty( $errors );
		$this->assertStringContainsString( 'avatar: value is required', $errors[0] );
	}

	/**
	 * Test image property with valid data URI.
	 */
	public function testImagePropertyWithDataUri()
	{
		$properties = [
			'logo' => [
				'type' => 'image',
				'required' => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Valid PNG data URI (1x1 pixel)
		$pngDataUri = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

		$dto->logo = $pngDataUri;
		$dto->validate();

		$this->assertEquals( $pngDataUri, $dto->logo );
	}

	/**
	 * Test image property with invalid base64.
	 */
	public function testImagePropertyWithInvalidBase64()
	{
		$properties = [
			'photo' => [
				'type' => 'image',
				'required' => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Invalid base64 string - validation happens on setter
		$this->expectException( Validation::class );
		$this->expectExceptionMessage( 'Validation failed for photo' );

		$dto->photo = 'This is not base64!@#$%';
	}

	/**
	 * Test image property with valid base64 but not an image.
	 */
	public function testImagePropertyWithNonImageData()
	{
		$properties = [
			'thumbnail' => [
				'type' => 'image',
				'required' => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Valid base64 but contains text "Hello World" instead of image - validation happens on setter
		$this->expectException( Validation::class );
		$this->expectExceptionMessage( 'Validation failed for thumbnail' );

		$dto->thumbnail = 'SGVsbG8gV29ybGQ=';
	}

	/**
	 * Test multiple image properties in a single DTO.
	 */
	public function testMultipleImageProperties()
	{
		$properties = [
			'avatar' => [
				'type' => 'image',
				'required' => true
			],
			'cover_photo' => [
				'type' => 'image',
				'required' => false
			],
			'thumbnail' => [
				'type' => 'image',
				'required' => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Set valid images
		$jpegBase64 = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=';
		$pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';
		$gifBase64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

		$dto->avatar = $jpegBase64;
		$dto->cover_photo = $pngBase64;
		$dto->thumbnail = $gifBase64;

		$dto->validate();

		$this->assertEquals( $jpegBase64, $dto->avatar );
		$this->assertEquals( $pngBase64, $dto->cover_photo );
		$this->assertEquals( $gifBase64, $dto->thumbnail );
	}

	/**
	 * Test image property in JSON output.
	 */
	public function testImagePropertyInJsonOutput()
	{
		$properties = [
			'icon' => [
				'type' => 'image',
				'required' => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Small GIF image
		$gifBase64 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
		$dto->icon = $gifBase64;

		$json = $dto->getAsJson();
		$expected = '{"icon":"' . $gifBase64 . '"}';

		$this->assertEquals( $expected, $json );
	}

	/**
	 * Test that SVG is rejected by default for security.
	 */
	public function testSvgRejectedByDefault()
	{
		$properties = [
			'vector_logo' => [
				'type' => 'image',
				'required' => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Simple SVG in base64
		$svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>';
		$svgBase64 = base64_encode( $svgContent );

		// SVG should be rejected (validation happens on setter)
		$this->expectException( Validation::class );
		$this->expectExceptionMessage( 'Validation failed for vector_logo' );

		$dto->vector_logo = $svgBase64;
	}

	/**
	 * Test image property with empty string.
	 */
	public function testImagePropertyWithEmptyString()
	{
		$properties = [
			'photo' => [
				'type' => 'image',
				'required' => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Empty string for optional field - validation is skipped for optional empty fields
		$dto->photo = '';

		// Validation passes because field is optional and empty
		$result = $dto->validate();
		$this->assertTrue( $result );

		// No errors for optional empty field
		$errors = $dto->getErrors();
		$this->assertEmpty( $errors );
	}

	/**
	 * Test image property with non-string value.
	 */
	public function testImagePropertyWithNonStringValue()
	{
		$properties = [
			'image' => [
				'type' => 'image',
				'required' => false
			]
		];

		$factory = new Factory( $properties );
		$dto = $factory->create();

		// Try setting numeric value - validation happens on setter
		$this->expectException( Validation::class );
		$this->expectExceptionMessage( 'Validation failed for image' );

		$dto->image = 12345;
	}
}