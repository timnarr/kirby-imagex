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
}
