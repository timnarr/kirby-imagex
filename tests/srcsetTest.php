<?php

namespace TimNarr;

use PHPUnit\Framework\TestCase;

@include_once __DIR__ . '/vendor/autoload.php';

class SrcsetTest extends TestCase
{
	public function testAddRatioBasedHeightToSrcsetPresetWith16to9Ratio()
	{
		$srcset = [
			'test-preset' => [
				'100w'  => ['width' => 100, 'crop' => false, 'quality' => 100, 'sharpen' => 10],
				'200w'  => ['width' => 200, 'crop' => true, 'quality' => 80],
				'300w'  => ['width' => 300, 'crop' => true],
				'400w'  => ['width' => 400],
			],
		];

		$ratioX = 16;
		$ratioY = 9;

		$expected = [
			'test-preset' => [
				'100w'  => ['width' => 100, 'height' => 56,  'crop' => false, 'quality' => 100, 'sharpen' => 10], // 56.25
				'200w'  => ['width' => 200, 'height' => 113, 'crop' => true, 'quality' => 80], // 112.5
				'300w'  => ['width' => 300, 'height' => 169, 'crop' => true], // 168.75
				'400w'  => ['width' => 400, 'height' => 225],
			],
		];

		$result = addRatioBasedHeightToSrcsetPreset($srcset, $ratioX, $ratioY);

		$this->assertEquals($expected, $result);
	}

	public function testAddRatioBasedHeightToSrcsetPresetWith3to2Ratio()
	{
		$srcset = [
			'test-preset' => [
				'100w'  => ['width' => 100, 'crop' => false, 'quality' => 100, 'sharpen' => 10],
				'200w'  => ['width' => 200, 'crop' => true, 'quality' => 80],
				'300w'  => ['width' => 300, 'crop' => true],
			],
		];

		$ratioX = 3;
		$ratioY = 2;

		$expected = [
			'test-preset' => [
				'100w'  => ['width' => 100, 'height' => 67,  'crop' => false, 'quality' => 100, 'sharpen' => 10], // 66.67
				'200w'  => ['width' => 200, 'height' => 133, 'crop' => true, 'quality' => 80], // 133.33
				'300w'  => ['width' => 300, 'height' => 200, 'crop' => true],
			],
		];

		$result = addRatioBasedHeightToSrcsetPreset($srcset, $ratioX, $ratioY);

		$this->assertEquals($expected, $result);
	}
}
