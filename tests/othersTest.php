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

	public function testGetSampleElementsWithThreeElements()
	{
		$array = ['400w' => ['width' => 400], '800w' => ['width' => 800], '1200w' => ['width' => 1200]];
		$result = getSampleElements($array);

		$this->assertEquals(['width' => 400], $result['first']);
		$this->assertEquals(['width' => 800], $result['middle']);
		$this->assertEquals(['width' => 1200], $result['last']);
	}

	public function testGetSampleElementsWithFiveElements()
	{
		$array = [
			'400w' => ['width' => 400],
			'600w' => ['width' => 600],
			'800w' => ['width' => 800],
			'1000w' => ['width' => 1000],
			'1200w' => ['width' => 1200],
		];
		$result = getSampleElements($array);

		$this->assertEquals(['width' => 400], $result['first']);
		$this->assertEquals(['width' => 800], $result['middle']);
		$this->assertEquals(['width' => 1200], $result['last']);
	}

	public function testGetSampleElementsWithSingleElement()
	{
		$array = ['400w' => ['width' => 400]];
		$result = getSampleElements($array);

		// With a single element, all samples should be the same
		$this->assertEquals(['width' => 400], $result['first']);
		$this->assertEquals(['width' => 400], $result['middle']);
		$this->assertEquals(['width' => 400], $result['last']);
	}

	public function testGetSampleElementsWithTwoElements()
	{
		$array = ['400w' => ['width' => 400], '1200w' => ['width' => 1200]];
		$result = getSampleElements($array);

		$this->assertEquals(['width' => 400], $result['first']);
		$this->assertEquals(['width' => 1200], $result['middle']); // Middle index is 1 for 2 elements
		$this->assertEquals(['width' => 1200], $result['last']);
	}

	public function testGetSampleElementsEmpty()
	{
		$this->expectExceptionMessage('[kirby-imagex] Input array cannot be empty.');
		getSampleElements([]);
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

	public function testUrlHandlerWithSrcsetString()
	{
		// Test handling of srcset strings with multiple URLs
		$srcset = 'http://example.com/image-400w.jpg 400w, http://example.com/image-800w.jpg 800w';
		$expected = '/image-400w.jpg 400w, /image-800w.jpg 800w';
		$this->assertEquals($expected, urlHandler($srcset, true, 'http://example.com'));
	}

	public function testResolveCompareFormatsWeightsMobilePreset()
	{
		$result = resolveCompareFormatsWeights('mobile');
		$this->assertEquals(['small' => 0.5, 'medium' => 0.3, 'large' => 0.2], $result);
	}

	public function testResolveCompareFormatsWeightsDesktopPreset()
	{
		$result = resolveCompareFormatsWeights('desktop');
		$this->assertEquals(['small' => 0.2, 'medium' => 0.3, 'large' => 0.5], $result);
	}

	public function testResolveCompareFormatsWeightsBalancedPreset()
	{
		$result = resolveCompareFormatsWeights('balanced');
		$this->assertEquals(['small' => 0.34, 'medium' => 0.33, 'large' => 0.33], $result);
	}

	public function testResolveCompareFormatsWeightsCustomArray()
	{
		$weights = ['small' => 0.4, 'medium' => 0.4, 'large' => 0.2];
		$result = resolveCompareFormatsWeights($weights);
		$this->assertEquals($weights, $result);
	}

	public function testResolveCompareFormatsWeightsInvalidPreset()
	{
		$this->expectExceptionMessage("[kirby-imagex] Invalid compareFormatsWeights preset 'invalid'. Available presets: mobile, desktop, balanced");
		resolveCompareFormatsWeights('invalid');
	}

	public function testResolveCompareFormatsWeightsInvalidSum()
	{
		$this->expectExceptionMessage('[kirby-imagex] compareFormatsWeights values must sum to 1.0.');
		resolveCompareFormatsWeights(['small' => 0.5, 'medium' => 0.5, 'large' => 0.5]);
	}

	public function testResolveCompareFormatsWeightsMissingKey()
	{
		$this->expectExceptionMessage("[kirby-imagex] compareFormatsWeights must have numeric 'small', 'medium', and 'large' keys.");
		resolveCompareFormatsWeights(['small' => 0.5, 'medium' => 0.5]);
	}

	public function testTransformForJsonWithNestedArrays()
	{
		// Test with nested arrays containing class and style
		$data = [
			'pictureAttributes' => [
				'class' => ['foo', 'bar'],
				'data-test' => 'value',
			],
			'sources' => [
				[
					'srcset' => 'image.jpg',
					'class' => ['baz'],
				],
			],
		];

		$expected = [
			'pictureAttributes' => [
				'class' => 'foo bar',
				'data-test' => 'value',
			],
			'sources' => [
				[
					'srcset' => 'image.jpg',
					'class' => 'baz',
				],
			],
		];

		$this->assertEquals($expected, transformForJson($data));
	}

	public function testTransformForJsonRemovesNullAndEmpty()
	{
		// Test that null values and empty strings are removed
		$data = [
			'valid' => 'value',
			'null' => null,
			'empty' => '',
			'zero' => 0,
			'false' => false,
		];

		$expected = [
			'valid' => 'value',
			'zero' => 0,
			'false' => false,
		];

		$this->assertEquals($expected, transformForJson($data));
	}
}
