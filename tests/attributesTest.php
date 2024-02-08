<?php

namespace TimNarr;

use PHPUnit\Framework\TestCase;

@include_once __DIR__ . '/vendor/autoload.php';

class HtmlAttributesTest extends TestCase
{
	public function testValidateAttributesValidInput()
	{
		$options = [
			'shared' => ['data-attribute' => 'my-attr-value', 'style' => ['background: red;']],
			'eager' => ['data-src' => 'image-eager.jpg'],
			'lazy' => [],
		];

		// Expect no exception
		$this->expectNotToPerformAssertions();

		validateAttributes($options);
	}

	public function testValidateAttributesInvalidInput()
	{
		$this->expectExceptionMessage('[kirby-imagex] Disallowed attributes detected: attribute "type" in "shared", attribute "srcset" in "eager", attribute "loading" in "lazy".');

		$options = [
			'shared' => ['type' => 'image/jpeg', 'data-attribute' => 'my-attr-value', 'style' => ['background: red;']],
			'eager' => ['data-src' => 'image-eager.jpg', 'srcset' => 'image-eager.jpg'],
			'lazy' => ['loading' => 'lazy', 'src' => 'image-lazy.jpg'],
		];

		validateAttributes($options);
	}

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
}
