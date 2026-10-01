<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Cms\File;
use Kirby\Cms\Page;
use Kirby\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

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
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("[kirby-imagex] Invalid ratio format '1:1'. Expected format \"x/y\"");
		getAspectRatioFromRatioString('1:1');
	}

	public function testInvalidGetAspectRatioFromRatioStringDash()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("[kirby-imagex] Invalid ratio format '1-1'. Expected format \"x/y\"");
		getAspectRatioFromRatioString('1-1');
	}

	public function testInvalidGetAspectRatioFromRatioStringWithZero()
	{
		// Same exception type as every other validation error, so callers can catch one type
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("[kirby-imagex] Invalid ratio '1/0'. \"x\" and \"y\" must be greater than 0.");
		getAspectRatioFromRatioString('1/0');
	}

	public function testGetAspectRatioFromImageThrowsForZeroDimensions()
	{
		$this->expectException(InvalidArgumentException::class);

		getAspectRatioFromImage(0, 0);
	}

	public function testGetAspectRatioWithExplicitRatioIgnoresDimensions()
	{
		$file = new File(['filename' => 'logo.svg', 'parent' => new Page(['slug' => 'test'])]);

		$this->assertEquals(['x' => 16, 'y' => 9], getAspectRatio('16/9', $file));
	}
}
