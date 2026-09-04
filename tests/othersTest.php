<?php

namespace TimNarr;

use Kirby\Cms\File;
use Kirby\Cms\Page;
use PHPUnit\Framework\TestCase;

class OthersTest extends TestCase
{
	private function fileWithFocus(string|null $focus): File
	{
		return new File([
			'filename' => 'test.jpg',
			'parent' => new Page(['slug' => 'test']),
			'content' => $focus !== null ? ['focus' => $focus] : [],
		]);
	}

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
		$src = 'default.jpg';
		$srcAttributes = ['lazy' => ['src' => 'lazy.jpg']];
		$loadingMode = 'lazy';

		$this->assertEquals('lazy.jpg', srcHandler($src, $srcAttributes, $loadingMode, false));
	}

	public function testSrcHandlerInEagerMode()
	{
		$src = 'default.jpg';
		$srcAttributes = ['lazy' => ['src' => 'lazy.jpg']];
		$loadingMode = 'eager';

		$this->assertEquals('default.jpg', srcHandler($src, $srcAttributes, $loadingMode, false));
	}

	public function testSrcHandlerWithCustomLazyloadingReturnsNull()
	{
		$src = 'default.jpg';
		$srcAttributes = [];
		$loadingMode = 'lazy';

		$this->assertNull(srcHandler($src, $srcAttributes, $loadingMode, true));
	}

	public function testSrcHandlerUserOverrideTakesPriorityOverCustomLazyloading()
	{
		$src = 'default.jpg';
		$srcAttributes = ['lazy' => ['src' => 'lazy.jpg']];
		$loadingMode = 'lazy';

		$this->assertEquals('lazy.jpg', srcHandler($src, $srcAttributes, $loadingMode, true));
	}

	public function testIsFormatSkippableWhenSmallestIsAdjacent()
	{
		$formats = ['avif', 'webp'];

		$this->assertTrue(isFormatSkippable('avif', $formats, 'webp'));
		$this->assertFalse(isFormatSkippable('webp', $formats, 'webp'));
	}

	public function testIsFormatSkippableWhenSmallestIsNotAdjacent()
	{
		// Regression test: with 3+ formats, every format preceding the smallest
		// one must be skipped, not just the format immediately before it.
		$formats = ['avif', 'webp', 'originalformat'];

		$this->assertTrue(isFormatSkippable('avif', $formats, 'originalformat'));
		$this->assertTrue(isFormatSkippable('webp', $formats, 'originalformat'));
		$this->assertFalse(isFormatSkippable('originalformat', $formats, 'originalformat'));
	}

	public function testIsFormatSkippableWithNoSmallestFormat()
	{
		$formats = ['avif', 'webp'];

		$this->assertFalse(isFormatSkippable('avif', $formats, ''));
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

	public function testResolveFocusValueWithFocusSet()
	{
		$this->assertEquals('23.5% 67%', resolveFocusValue($this->fileWithFocus('23.5% 67%')));
	}

	public function testResolveFocusValueWithNoFocusField()
	{
		$this->assertEquals('center', resolveFocusValue($this->fileWithFocus(null)));
	}

	public function testResolveFocusValueWithEmptyFocusField()
	{
		$this->assertEquals('center', resolveFocusValue($this->fileWithFocus('')));
	}

	public function testResolveFocusValueWithKeyword()
	{
		$this->assertEquals('top', resolveFocusValue($this->fileWithFocus('top')));
	}

	public function testResolveFocusValueFallsBackToCenterOnInjectionAttempt()
	{
		// Regression test: a malicious/malformed value in the Panel-editable
		// `focus` field must never be passed through into generated CSS as-is.
		$malicious = '0% 0%; } </style><script>alert(1)</script>';
		$this->assertEquals('center', resolveFocusValue($this->fileWithFocus($malicious)));
	}

	public function testResolveFocusValueFallsBackToCenterOnUnknownKeyword()
	{
		$this->assertEquals('center', resolveFocusValue($this->fileWithFocus('not-a-real-keyword')));
	}

	public function testIsValidCssPositionValueAcceptsPercentagePairs()
	{
		$this->assertTrue(isValidCssPositionValue('50% 30%'));
		$this->assertTrue(isValidCssPositionValue('23.45% 67.89%'));
		$this->assertTrue(isValidCssPositionValue('-10% 110%'));
	}

	public function testIsValidCssPositionValueAcceptsSingleKeyword()
	{
		$this->assertTrue(isValidCssPositionValue('center'));
		$this->assertTrue(isValidCssPositionValue('top'));
	}

	public function testIsValidCssPositionValueAcceptsLengthUnits()
	{
		$this->assertTrue(isValidCssPositionValue('10px 5em'));
		$this->assertTrue(isValidCssPositionValue('1.5rem center'));
	}

	public function testIsValidCssPositionValueRejectsInjectionAttempt()
	{
		$this->assertFalse(isValidCssPositionValue('0% 0%; } </style><script>alert(1)</script>'));
		$this->assertFalse(isValidCssPositionValue('50%") } * { color: red'));
	}

	public function testIsValidCssPositionValueRejectsUnknownTokens()
	{
		$this->assertFalse(isValidCssPositionValue('not-a-real-keyword'));
		$this->assertFalse(isValidCssPositionValue('50% 30% 10%'));
		$this->assertFalse(isValidCssPositionValue(''));
	}
}
