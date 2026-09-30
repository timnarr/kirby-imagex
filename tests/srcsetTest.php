<?php

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SrcsetTest extends TestCase
{
	public function testAddRatioBasedHeightToSrcsetPresetWith16to9Ratio()
	{
		$srcset = [
			'test-preset' => [
				'100w'  => ['width' => 100, 'crop' => true, 'quality' => 100, 'sharpen' => 10],
				'200w'  => ['width' => 200, 'crop' => true, 'quality' => 80],
				'300w'  => ['width' => 300, 'crop' => true],
				'400w'  => ['width' => 400, 'crop' => true],
			],
		];

		$ratioX = 16;
		$ratioY = 9;

		$expected = [
			'test-preset' => [
				'100w'  => ['width' => 100, 'height' => 56,  'crop' => true, 'quality' => 100, 'sharpen' => 10], // 56.25
				'200w'  => ['width' => 200, 'height' => 113, 'crop' => true, 'quality' => 80], // 112.5
				'300w'  => ['width' => 300, 'height' => 169, 'crop' => true], // 168.75
				'400w'  => ['width' => 400, 'height' => 225, 'crop' => true],
			],
		];

		$result = addRatioBasedHeightToSrcsetPreset($srcset, $ratioX, $ratioY);

		$this->assertEquals($expected, $result);
	}

	public function testAddRatioBasedHeightToSrcsetPresetWith3to2Ratio()
	{
		$srcset = [
			'test-preset' => [
				'100w'  => ['width' => 100, 'crop' => true, 'quality' => 100, 'sharpen' => 10],
				'200w'  => ['width' => 200, 'crop' => true, 'quality' => 80],
				'300w'  => ['width' => 300, 'crop' => true],
			],
		];

		$ratioX = 3;
		$ratioY = 2;

		$expected = [
			'test-preset' => [
				'100w'  => ['width' => 100, 'height' => 67,  'crop' => true, 'quality' => 100, 'sharpen' => 10], // 66.67
				'200w'  => ['width' => 200, 'height' => 133, 'crop' => true, 'quality' => 80], // 133.33
				'300w'  => ['width' => 300, 'height' => 200, 'crop' => true],
			],
		];

		$result = addRatioBasedHeightToSrcsetPreset($srcset, $ratioX, $ratioY);

		$this->assertEquals($expected, $result);
	}

	public function testNormalizeSrcsetPresetWidthList()
	{
		$this->assertSame(
			['400w' => ['width' => 400], '800w' => ['width' => 800]],
			normalizeSrcsetPreset([400, 800], 'default')
		);
	}

	public function testNormalizeSrcsetPresetWidthToDescriptor()
	{
		$this->assertSame(
			['1x' => ['width' => 400], '2x' => ['width' => 800]],
			normalizeSrcsetPreset([400 => '1x', 800 => '2x'], 'default')
		);
	}

	public function testNormalizeSrcsetPresetLongFormIsUnchanged()
	{
		$preset = ['400w' => ['width' => 400, 'quality' => 80, 'format' => 'webp']];

		$this->assertSame($preset, normalizeSrcsetPreset($preset, 'default'));
	}

	public function testNormalizeSrcsetPresetThrowsWithoutWidth()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("'default' entry '400w' needs a positive 'width'");

		normalizeSrcsetPreset(['400w' => ['quality' => 80]], 'default');
	}

	public function testNormalizeSrcsetPresetThrowsWhenEmpty()
	{
		$this->expectException(InvalidArgumentException::class);

		normalizeSrcsetPreset([], 'default');
	}
}
