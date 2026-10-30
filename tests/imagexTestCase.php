<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Filesystem\Dir;
use PHPUnit\Framework\TestCase;

/**
 * Imagex with a fixed compareFormats result per image filename, so tests can
 * cover every outcome instead of whatever the image encoders happen to produce.
 */
class PinnedFormatsImagex extends Imagex
{
	public static array $smallest = [];
	public static int $calls = 0;

	public function getSmallestFormatForImage(File|null $image = null, string|null $ratio = null): string
	{
		self::$calls++;

		return self::$smallest[($image ?? $this->image)->filename()];
	}
}

/**
 * Boots a fresh Kirby app per test in a temp dir with generated fixture images.
 */
abstract class ImagexTestCase extends TestCase
{
	protected string $root;

	protected function setUp(): void
	{
		$this->root = sys_get_temp_dir() . '/kirby-imagex-tests-' . uniqid();
		Dir::make($this->root . '/content/test');
		// Only a viewBox: Kirby derives 160x90, but can't resize SVGs
		file_put_contents($this->root . '/content/test/icon.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 90"></svg>');
		$this->createJpeg('image.jpg', 1600, 1200);
		$this->createJpeg('landscape.jpg', 2100, 900);
		// no width/height/viewBox — Kirby reports 0x0
		file_put_contents($this->root . '/content/test/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
		$this->app();
	}

	protected function tearDown(): void
	{
		Dir::remove($this->root);
	}

	protected function createJpeg(string $filename, int $width, int $height): void
	{
		imagejpeg(imagecreatetruecolor($width, $height), $this->root . '/content/test/' . $filename);
	}

	/**
	 * Boots a fresh Kirby instance with srcset presets for all default formats.
	 * `$options` are merged into the config options (flat keys, e.g. 'timnarr.imagex.formats').
	 * `$components` replace Kirby core components, e.g. to count 'file::version' (thumb) calls.
	 */
	protected function app(array $options = [], string $indexUrl = 'https://example.com', array $components = []): App
	{
		$preset = fn (string|null $format = null) => [
			'400w' => array_filter(['width' => 400, 'format' => $format]),
			'800w' => array_filter(['width' => 800, 'format' => $format]),
		];

		return new App([
			'roots' => ['index' => $this->root],
			'urls' => ['index' => $indexUrl],
			'components' => $components,
			'options' => [
				'thumbs' => ['srcsets' => [
					'default' => $preset(),
					'default-webp' => $preset('webp'),
					'default-avif' => $preset('avif'),
				]],
				...$options,
			],
		]);
	}

	protected function image(string $filename = 'image.jpg'): File
	{
		return App::instance()->page('test')->image($filename);
	}

	protected function imagex(array $options = []): Imagex
	{
		return new Imagex([
			'image' => $this->image(),
			'ratio' => '16/9',
			'srcset' => 'default',
			'compareFormats' => false,
			...$options,
		]);
	}

	/**
	 * Creates an Imagex with compareFormats enabled and a fixed smallest format per image filename.
	 */
	protected function pinnedImagex(array $smallest, array $options = []): PinnedFormatsImagex
	{
		PinnedFormatsImagex::$smallest = $smallest;
		PinnedFormatsImagex::$calls = 0;

		return new PinnedFormatsImagex([
			'image' => $this->image(),
			'ratio' => '16/9',
			'compareFormats' => true,
			...$options,
		]);
	}
}
