<?php

namespace TimNarr;

use PHPUnit\Framework\TestCase;

@include_once __DIR__ . '/vendor/autoload.php';

class HtmlAttributesTest extends TestCase
{
	public function testValidateAttributeTypesInvalidInput()
	{
		$this->expectExceptionMessage('[kirby-imagex] Type mismatch detected: attribute "style" in "shared" expected to be array, string given.');

		$options = [
			'shared' => ['style' => 'background: red;'],
			'eager' => ['data-src' => 'image-eager.jpg', 'srcset' => 'image-eager.jpg'],
			'lazy' => ['loading' => 'lazy', 'src' => 'image-lazy.jpg'],
		];

		validateAttributeTypes($options);
	}

	public function testMergeHTMLAttributesEager()
	{
		$options = [
			'shared' => [
				'class' => 'my-image',
				'style' => ['color: red;'],
			],
			'eager' => [
				'class' => 'my-image--eager',
				'style' => [
					'background-color: blue;',
					'border: 1px solid red;',
				],
			],
		];
		$loadingMode = 'eager';
		$defaultOptions = ['shared' => [], 'eager' => [], 'lazy' => []];

		$expected = [
			'class' => [
				'my-image',
				'my-image--eager',
			],
			'style' => [
				'color: red;',
				'background-color: blue;',
				'border: 1px solid red;',
			],
		];

		$result = mergeHTMLAttributes($options, $loadingMode, $defaultOptions);

		$this->assertEquals($expected, $result);
	}

	public function testMergeHTMLAttributesInvalidLoadingMode()
	{
		$this->expectExceptionMessage('[kirby-imagex] Invalid loadingMode: "x".');

		mergeHTMLAttributes([], 'x', []);
	}

	public function testMergeHTMLAttributesLazy()
	{
		$options = [
			'shared' => [
				'class' => 'my-image',
				'style' => ['color: red;'],
			],
			'lazy' => [
				'class' => [
					'my-image--lazy',
					'js-image-lazy',
				],
				'style' => [
					'background-color: blue;',
					'border: 1px solid red;',
				],
			],
		];
		$loadingMode = 'lazy';
		$defaultOptions = ['shared' => [], 'eager' => [], 'lazy' => []];

		$expected = [
			'class' => [
				'my-image',
				'my-image--lazy',
				'js-image-lazy',
			],
			'style' => [
				'color: red;',
				'background-color: blue;',
				'border: 1px solid red;',
			],
		];

		$result = mergeHTMLAttributes($options, $loadingMode, $defaultOptions);

		$this->assertEquals($expected, $result);
	}

	public function testMergeHTMLAttributesUserOverridesDefaults()
	{
		// Test that user attributes override default attributes
		$userAttributes = [
			'shared' => [
				'width' => 500,
				'height' => 300,
			],
			'lazy' => [
				'src' => 'custom-image.jpg',
				'loading' => 'eager',
			],
		];
		$loadingMode = 'lazy';
		$defaultAttributes = [
			'shared' => [
				'width' => 1000,
				'height' => 600,
				'decoding' => 'async',
			],
			'lazy' => [
				'src' => 'default-image.jpg',
				'loading' => 'lazy',
				'data-src' => 'default-data-image.jpg',
			],
		];

		$expected = [
			'width' => 500, // User override
			'height' => 300, // User override
			'decoding' => 'async', // From defaults
			'src' => 'custom-image.jpg', // User override
			'loading' => 'eager', // User override
			'data-src' => 'default-data-image.jpg', // From defaults (not overridden by user)
		];

		$result = mergeHTMLAttributes($userAttributes, $loadingMode, $defaultAttributes);

		$this->assertEquals($expected, $result);
	}

	public function testApplyUrlHandlerToAttributesWithRelativeUrlsActive()
	{
		// Test that URL attributes are converted to relative URLs when relativeUrls is active
		$attributes = [
			'src' => 'https://example.com/media/image.jpg',
			'srcset' => 'https://example.com/media/image-400.jpg 400w, https://example.com/media/image-800.jpg 800w',
			'data-src' => 'https://example.com/media/lazy-image.jpg',
			'data-srcset' => 'https://example.com/media/lazy-400.jpg 400w',
			'alt' => 'My Image',
			'width' => 800,
		];

		$expected = [
			'src' => '/media/image.jpg',
			'srcset' => '/media/image-400.jpg 400w, /media/image-800.jpg 800w',
			'data-src' => '/media/lazy-image.jpg',
			'data-srcset' => '/media/lazy-400.jpg 400w',
			'alt' => 'My Image',
			'width' => 800,
		];

		// Explicitly enable relativeUrls for this test
		$result = applyUrlHandlerToAttributes($attributes, true, 'https://example.com');

		$this->assertEquals($expected, $result);
	}

	public function testApplyUrlHandlerToAttributesWithRelativeUrlsInactive()
	{
		// Test that URLs are not modified when relativeUrls is inactive
		$attributes = [
			'src' => 'https://example.com/media/image.jpg',
			'srcset' => 'https://example.com/media/image-400.jpg 400w',
			'data-src' => 'https://example.com/media/lazy-image.jpg',
			'alt' => 'My Image',
		];

		// When relativeUrls is false, attributes should remain unchanged
		$expected = $attributes;

		// Explicitly disable relativeUrls for this test
		$result = applyUrlHandlerToAttributes($attributes, false, 'https://example.com');

		$this->assertEquals($expected, $result);
	}

	public function testApplyUrlHandlerToAttributesWithExternalUrls()
	{
		// Test that external URLs are not modified even when relativeUrls is active
		$attributes = [
			'src' => 'https://cdn.external.com/image.jpg',
			'srcset' => 'https://cdn.external.com/image-400.jpg 400w',
			'data-src' => 'https://example.com/media/internal.jpg', // Internal URL
			'alt' => 'External Image',
		];

		$expected = [
			'src' => 'https://cdn.external.com/image.jpg', // External, unchanged
			'srcset' => 'https://cdn.external.com/image-400.jpg 400w', // External, unchanged
			'data-src' => '/media/internal.jpg', // Internal, converted
			'alt' => 'External Image',
		];

		// Enable relativeUrls and set siteUrl for this test
		$result = applyUrlHandlerToAttributes($attributes, true, 'https://example.com');

		$this->assertEquals($expected, $result);
	}

	public function testApplyUrlHandlerToAttributesWithMixedValues()
	{
		// Test that non-string values in URL attributes are not processed
		$attributes = [
			'src' => 'https://example.com/image.jpg',
			'width' => 800, // Integer, should be ignored
			'srcset' => null, // Null, should be ignored
			'data-src' => false, // Boolean, should be ignored
		];

		$expected = [
			'src' => '/image.jpg', // Only this should be processed
			'width' => 800,
			'srcset' => null,
			'data-src' => false,
		];

		// Enable relativeUrls for this test
		$result = applyUrlHandlerToAttributes($attributes, true, 'https://example.com');

		$this->assertEquals($expected, $result);
	}

	public function testApplyUrlHandlerToAttributesEmptyArray()
	{
		// Test that empty array returns empty array
		$attributes = [];
		$expected = [];

		$result = applyUrlHandlerToAttributes($attributes, true, 'https://example.com');

		$this->assertEquals($expected, $result);
	}
}
