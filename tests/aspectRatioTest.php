<?php

namespace TimNarr;

use PHPUnit\Framework\TestCase;

@include_once __DIR__ . '/vendor/autoload.php';

class AspectRatioTest extends TestCase
{
	public function testGreatestCommonDivisor()
	{
		$this->assertEquals(1, greatestCommonDivisor(1, 1));
		$this->assertEquals(120, greatestCommonDivisor(1920, 1080));
	}

	public function testGetAspectRatioFromImage()
	{
		$this->assertEquals(['x' => 16, 'y' => 9], getAspectRatioFromImage(1920, 1080));
	}

	public function testGetAspectRatioFromRatioString()
	{
		$this->assertEquals(['x' => 16, 'y' => 9], getAspectRatioFromRatioString('16/9'));
	}

	public function testInvalidGetAspectRatioFromRatioStringColon()
	{
		$this->expectExceptionMessage('[kirby-imagex] Invalid ratio format. Expected format "x/y".');
		getAspectRatioFromRatioString('1:1');
	}

	public function testInvalidGetAspectRatioFromRatioStringDash()
	{
		$this->expectExceptionMessage('[kirby-imagex] Invalid ratio format. Expected format "x/y".');
		getAspectRatioFromRatioString('1-1');
	}

	public function testInvalidGetAspectRatioFromRatioStringWithZero()
	{
		$this->expectExceptionMessage('[kirby-imagex] Invalid ratio format. "x" and "y" must be greater than 0.');
		getAspectRatioFromRatioString('1/0');
	}
}
