<?php

namespace TimNarr;

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Filesystem\Dir;
use PHPUnit\Framework\TestCase;

class ImagexTest extends TestCase
{
	private string $root;

	protected function setUp(): void
	{
		$this->root = sys_get_temp_dir() . '/kirby-imagex-tests-' . uniqid();
		Dir::make($this->root . '/content/test');
		$this->createJpeg('image.jpg', 1600, 1200);
		$this->createJpeg('landscape.jpg', 2100, 900);
		$this->app();
	}

	protected function tearDown(): void
	{
		Dir::remove($this->root);
	}

	private function createJpeg(string $filename, int $width, int $height): void
	{
		imagejpeg(imagecreatetruecolor($width, $height), $this->root . '/content/test/' . $filename);
	}

	/**
	 * Boots a fresh Kirby instance with srcset presets for all default formats.
	 * `$options` are merged into the config options (flat keys, e.g. 'timnarr.imagex.formats').
	 */
	private function app(array $options = []): App
	{
		$preset = fn (string|null $format = null) => [
			'400w' => array_filter(['width' => 400, 'format' => $format]),
			'800w' => array_filter(['width' => 800, 'format' => $format]),
		];

		return new App([
			'roots' => ['index' => $this->root],
			'urls' => ['index' => 'https://example.com'],
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

	private function image(string $filename = 'image.jpg'): File
	{
		return App::instance()->page('test')->image($filename);
	}

	private function imagex(array $options = []): Imagex
	{
		return new Imagex([
			'image' => $this->image(),
			'ratio' => '16/9',
			'srcset' => 'default',
			'compareFormats' => false,
			...$options,
		]);
	}

	public function testArtDirectionStylesAreEmptyWithoutArtDirection()
	{
		$this->assertSame('', $this->imagex()->getArtDirectionStyles());
	}

	public function testArtDirectionStylesAreEmptyWhenNoSourceChangesAnything()
	{
		$imagex = $this->imagex(['artDirection' => [
			['media' => '(min-width: 800px)', 'ratio' => '16/9'],
		]]);

		$this->assertSame('', $imagex->getArtDirectionStyles());
		$this->assertNull($imagex->getImgAttributes()['id'] ?? null);
	}

	public function testArtDirectionStylesLetTheFirstMatchingSourceWin()
	{
		// <picture> picks the first matching <source>; CSS applies the last matching
		// rule. With both queries matching (>= 1200px), 21/9 must win.
		$css = $this->imagex(['artDirection' => [
			['media' => '(min-width: 1200px)', 'ratio' => '21/9'],
			['media' => '(min-width: 600px)', 'ratio' => '4/3'],
		]])->getArtDirectionStyles();

		$this->assertMatchesRegularExpression('/min-width: 600px.*aspect-ratio: 4 \/ 3.*min-width: 1200px.*aspect-ratio: 21 \/ 9/', $css);
	}

	public function testArtDirectionStylesResetRatioForEarlierSourceMatchingTheDefault()
	{
		// The 1200px source uses the default ratio, but must still get a rule —
		// otherwise the also-matching 600px rule would apply 4/3 to it.
		$css = $this->imagex(['artDirection' => [
			['media' => '(min-width: 1200px)', 'ratio' => '16/9'],
			['media' => '(min-width: 600px)', 'ratio' => '4/3'],
		]])->getArtDirectionStyles();

		$this->assertMatchesRegularExpression('/min-width: 600px.*aspect-ratio: 4 \/ 3.*min-width: 1200px.*aspect-ratio: 16 \/ 9/', $css);
	}

	public function testArtDirectionStylesUseSourceImageRatioForIntrinsic()
	{
		$css = $this->imagex(['artDirection' => [
			['media' => '(min-width: 800px)', 'image' => $this->image('landscape.jpg')],
		]])->getArtDirectionStyles();

		$this->assertStringContainsString('aspect-ratio: 7 / 3 !important;', $css);
	}

	public function testArtDirectionStylesTargetTheImgId()
	{
		$imagex = $this->imagex(['artDirection' => [
			['media' => '(min-width: 800px)', 'ratio' => '1/1'],
		]]);
		$id = $imagex->getImgAttributes()['id'];

		$this->assertStringContainsString('#' . $id . ' {', $imagex->getArtDirectionStyles());
	}
}
