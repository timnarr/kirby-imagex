<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;

/**
 * Integration tests for the `thumbRatio()` file method.
 */
class ThumbRatioTest extends ImagexTestCase
{
	public function testDerivesTheHeightFromTheRatio()
	{
		$thumb = $this->image()->thumbRatio('16/9', ['width' => 800]);

		$this->assertSame(800, $thumb->width());
		$this->assertSame(450, $thumb->height());
	}

	public function testDefaultsToTheImageWidth()
	{
		$thumb = $this->image()->thumbRatio('1/1');

		$this->assertSame(1600, $thumb->width());
		$this->assertSame(1600, $thumb->height());
	}

	public function testCropDefaultsToTrueSoTheFocusPointIsUsed()
	{
		file_put_contents($this->root . '/content/test/image.jpg.txt', 'Focus: 20% 30%');

		$url = $this->image()->thumbRatio('16/9', ['width' => 800])->url();

		$this->assertStringContainsString('image-800x450-crop-20-30', $url);
	}

	public function testCropFalseIsReplacedWithTrueSoTheRatioIsKept()
	{
		$thumb = $this->image()->thumbRatio('16/9', ['width' => 800, 'crop' => false]);

		$this->assertSame(800, $thumb->width());
		$this->assertSame(450, $thumb->height());
	}

	public function testHeightThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('thumbRatio() derives the height from the ratio');

		$this->image()->thumbRatio('16/9', ['width' => 800, 'height' => 100]);
	}

	public function testCropPositionIsKept()
	{
		$url = $this->image()->thumbRatio('16/9', ['width' => 800, 'crop' => 'top left'])->url();

		$this->assertStringContainsString('image-800x450-crop-top-left', $url);
	}
}
