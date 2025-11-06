<?php

namespace TimNarr;

use PHPUnit\Framework\TestCase;

@include_once __DIR__ . '/vendor/autoload.php';

class OthersTest extends TestCase
{
	public function testNormalizeFormat()
	{
		$this->assertEquals('jpeg', normalizeFormat('JPG'));
		$this->assertEquals('png', normalizeFormat('PNG'));
	}


	public function testUrlHandlerWithRelativeUrlActive()
	{
		$this->assertEquals('/path/to/resource', urlHandler('http://example.com/path/to/resource', true, 'http://example.com'));
	}

	public function testUrlHandlerWithRelativeUrlInactive()
	{
		$this->assertEquals('http://example.com/path/to/resource', urlHandler('http://example.com/path/to/resource', false, 'http://example.com'));
	}

	public function testUrlHandlerWithExternalUrl()
	{
		// External URLs should not be modified, even when relativeUrls is active
		$this->assertEquals('https://external.com/image.jpg', urlHandler('https://external.com/image.jpg', true, 'http://example.com'));
	}

	public function testFindSmallestValueAndKey()
	{
		$array = ['large' => 3, 'small' => 1, 'medium' => 2];
		$expected = 'small';
		$this->assertEquals($expected, findSmallestValueAndKey($array));
	}

	public function testFindSmallestValueAndKeyEmptyArray()
	{
		$this->expectExceptionMessage('[kirby-imagex] Input array cannot be empty.');
		findSmallestValueAndKey([]);
	}

	public function testFindMiddleArrayOdd()
	{
		$array = ['1' => 1, '2' => 2, '3' => 3] ;
		$expected = ['middleKey' => '2', 'middleValue' => 2];
		$this->assertEquals($expected, findMiddleArray($array));
	}

	public function testFindMiddleArrayEven()
	{
		$array = ['1' => 1, '2' => 2, '3' => 3, '4' => 4];
		$expected = ['middleKey' => '3', 'middleValue' => 3];
		$this->assertEquals($expected, findMiddleArray($array));
	}

	public function testFindMiddleArrayEmpty()
	{
		$this->expectExceptionMessage('[kirby-imagex] Input array cannot be empty.');
		findMiddleArray([]);
	}

	public function testSrcHandlerInLazyMode()
	{
		// Assuming customLazyloading option is true and srcAttributes define a 'lazy' loading mode
		$src = 'default.jpg';
		$srcAttributes = ['lazy' => ['src' => 'lazy.jpg']];
		$loadingMode = 'lazy';

		// You'll need to set up mocks or similar for kirby()->option and urlHandler calls
		$this->assertEquals('lazy.jpg', srcHandler($src, $srcAttributes, $loadingMode));
	}

	public function testSrcHandlerInEagerMode()
	{
		// Assuming customLazyloading option is false
		$src = 'default.jpg';
		$srcAttributes = ['lazy' => ['src' => 'lazy.jpg']];
		$loadingMode = 'eager';

		// Setup mocks or similar for kirby()->option calls
		$this->assertEquals('default.jpg', srcHandler($src, $srcAttributes, $loadingMode));
	}
}
