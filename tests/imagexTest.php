<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Exception\InvalidArgumentException;
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
		// no width/height/viewBox — Kirby reports 0x0
		file_put_contents($this->root . '/content/test/logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
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
	private function app(array $options = [], string $indexUrl = 'https://example.com'): App
	{
		$preset = fn (string|null $format = null) => [
			'400w' => array_filter(['width' => 400, 'format' => $format]),
			'800w' => array_filter(['width' => 800, 'format' => $format]),
		];

		return new App([
			'roots' => ['index' => $this->root],
			'urls' => ['index' => $indexUrl],
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

	public function testEagerImgKeepsSrcWithCustomLazyloading()
	{
		$this->app(['timnarr.imagex.customLazyloading' => true]);
		$attributes = $this->imagex(['loading' => 'eager'])->getImgAttributes();

		$this->assertStringContainsString('image-400x225', $attributes['src']);
		$this->assertStringContainsString('800w', $attributes['srcset']);
		$this->assertNull($attributes['data-src'] ?? null);
	}

	public function testEagerImgKeepsSrcWithCustomLazyloadingAndNoSrcsetInImg()
	{
		$this->app([
			'timnarr.imagex.customLazyloading' => true,
			'timnarr.imagex.noSrcsetInImg' => true,
		]);
		$attributes = $this->imagex(['loading' => 'eager'])->getImgAttributes();

		$this->assertStringContainsString('image-400x225', $attributes['src']);
		$this->assertNull($attributes['srcset'] ?? null);
	}

	public function testLazyImgMovesSrcToDataSrcWithCustomLazyloading()
	{
		$this->app(['timnarr.imagex.customLazyloading' => true]);
		$attributes = $this->imagex()->getImgAttributes();

		$this->assertNull($attributes['src']);
		$this->assertNull($attributes['srcset']);
		$this->assertNull($attributes['loading']);
		$this->assertStringContainsString('image-400x225', $attributes['data-src']);
		$this->assertStringContainsString('800w', $attributes['data-srcset']);
	}

	public function testUserSrcOverridesLazyDefaultWithCustomLazyloading()
	{
		$this->app(['timnarr.imagex.customLazyloading' => true]);
		$attributes = $this->imagex([
			'attributes' => ['img' => ['lazy' => ['src' => 'placeholder.svg']]],
		])->getImgAttributes();

		$this->assertSame('placeholder.svg', $attributes['src']);
	}

	public function testNativeLazyImgHasSrcAndSrcset()
	{
		$attributes = $this->imagex()->getImgAttributes();

		$this->assertSame('lazy', $attributes['loading']);
		$this->assertStringContainsString('image-400x225', $attributes['src']);
		$this->assertStringContainsString('800w', $attributes['srcset']);
	}

	public function testRelativeUrlsKeepSubfolderPath()
	{
		$this->app(['timnarr.imagex.relativeUrls' => true], 'https://example.com/blog');

		$attributes = $this->imagex()->getImgAttributes();

		$this->assertStringStartsWith('/blog/media/', $attributes['src']);
		$this->assertStringNotContainsString('https://', $attributes['srcset']);
	}

	public function testShorthandSrcsetPresetsAreSupported()
	{
		$this->app(['thumbs' => ['srcsets' => [
			'default' => [400, 800],
			'default-webp' => ['400w' => ['width' => 400, 'format' => 'webp']],
			'default-avif' => ['400w' => ['width' => 400, 'format' => 'avif']],
		]]]);

		$attributes = $this->imagex()->getImgAttributes();

		$this->assertSame(400, $attributes['width']);
		$this->assertSame(225, $attributes['height']);
		$this->assertStringContainsString('image-800x450-crop.jpg 800w', $attributes['srcset']);
	}

	public function testIntrinsicRatioThrowsForImageWithoutDimensions()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Cannot use ratio 'intrinsic' for 'test/logo.svg'");

		getAspectRatio('intrinsic', App::instance()->page('test')->file('logo.svg'));
	}

	public function testArtDirectionStylesEscapeUserId()
	{
		$css = $this->imagex([
			'attributes' => ['img' => ['id' => '1-hero']],
			'artDirection' => [['media' => '(min-width: 800px)', 'ratio' => '1/1']],
		])->getArtDirectionStyles();

		$this->assertStringContainsString('#\31 -hero {', $css);
	}

	public function testArtDirectionSourceWithoutMediaThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("artDirection[0] is missing 'media'");

		$this->imagex(['artDirection' => [['ratio' => '1/1']]]);
	}

	public function testArtDirectionSourceWithUnknownKeyThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('artDirection[0] has unknown key(s): ratios');

		$this->imagex(['artDirection' => [['media' => '(min-width: 800px)', 'ratios' => '1/1']]]);
	}

	public function testArtDirectionSourceWithInvalidImageThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("artDirection[0]: 'image' must be an instance of Kirby\\Cms\\File or null.");

		$this->imagex(['artDirection' => [['media' => '(min-width: 800px)', 'image' => 'landscape.jpg']]]);
	}

	public function testArtDirectionSourceWithInvalidRatioThrowsOnConstruction()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("artDirection[0]: Invalid ratio format '16:9'");

		$this->imagex(['artDirection' => [['media' => '(min-width: 800px)', 'ratio' => '16:9']]]);
	}

	public function testArtDirectionIntrinsicRatioErrorNamesEntry()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("artDirection[1]: Cannot use ratio 'intrinsic' for 'test/logo.svg'");

		$this->imagex(['artDirection' => [
			['media' => '(min-width: 1200px)', 'ratio' => '1/1'],
			['media' => '(min-width: 800px)', 'image' => $this->image('logo.svg')],
		]]);
	}

	public function testArtDirectionSourceWithNullImageFallsBackToMainImage()
	{
		$sources = $this->imagex(['artDirection' => [
			['media' => '(min-width: 800px)', 'ratio' => '1/1', 'image' => null],
		]])->getPictureSources();

		$this->assertStringContainsString('image-400x400-crop', $sources[0]['srcset']);
	}

	public function testInvalidRatioThrowsOnConstruction()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Invalid ratio format '16:9'");

		$this->imagex(['ratio' => '16:9']);
	}

	public function testOnlyImageIsRequired()
	{
		$imagex = new Imagex(['image' => $this->image()]);
		$attributes = $imagex->getImgAttributes();

		// defaults: ratio 'intrinsic' (4/3 for the 1600x1200 fixture), srcset 'default', loading 'lazy'
		$this->assertSame(400, $attributes['width']);
		$this->assertSame(300, $attributes['height']);
		$this->assertSame('lazy', $attributes['loading']);
	}

	public function testNullOptionsFallBackToDefaults()
	{
		$imagex = new Imagex(['image' => $this->image(), 'ratio' => null, 'loading' => null, 'attributes' => null]);

		$this->assertSame(300, $imagex->getImgAttributes()['height']);
	}

	public function testUnknownOptionThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown option(s): ration. Allowed:');

		$this->imagex(['ration' => '1/1']);
	}

	public function testUnknownAttributesElementThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'attributes' has unknown key(s): image. Allowed: img, picture, sources");

		$this->imagex(['attributes' => ['image' => ['alt' => 'Alt']]]);
	}

	public function testNonArrayAttributesThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'attributes' must be an array with the keys: img, picture, sources");

		$this->imagex(['attributes' => 'my-class']);
	}

	public function testNonStringLoadingNamesType()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'loading' must be 'eager' or 'lazy'. Got: bool");

		$this->imagex(['loading' => true]);
	}

	public function testNonArrayAttributesElementThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'attributes.img' must be an array.");

		$this->imagex(['attributes' => ['img' => 'my-class']]);
	}

	public function testArtDirectionAcceptsFlatAttributes()
	{
		$sources = $this->imagex(['artDirection' => [
			['media' => '(min-width: 800px)', 'attributes' => ['data-landscape' => 'true', 'class' => 'a b']],
		]])->getPictureSources();

		$this->assertSame('true', $sources[0]['data-landscape']);
		$this->assertSame(['a', 'b'], $sources[0]['class']);
	}

	public function testArtDirectionAcceptsClassStringInStructuredAttributes()
	{
		$sources = $this->imagex(['artDirection' => [
			['media' => '(min-width: 800px)', 'attributes' => ['lazy' => ['class' => 'a b']]],
		]])->getPictureSources();

		$this->assertSame(['a', 'b'], $sources[0]['class']);
	}

	public function testArtDirectionRejectsMixedAttributes()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Attributes mix flat keys');

		$this->imagex(['artDirection' => [
			['media' => '(min-width: 800px)', 'attributes' => ['class' => 'a', 'lazy' => ['data-x' => 'y']]],
		]]);
	}

	public function testNonBooleanPluginOptionThrows()
	{
		$this->app(['timnarr.imagex.customLazyloading' => 'yes']);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'timnarr.imagex.customLazyloading' must be a boolean. Got: string");

		$this->imagex();
	}

	public function testInvalidFormatsOptionThrows()
	{
		$this->app(['timnarr.imagex.formats' => 'avif']);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'timnarr.imagex.formats' must be an array of format names");

		$this->imagex();
	}

	public function testNonArrayArtDirectionThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'artDirection' must be an array of sources.");

		$this->imagex(['artDirection' => '(min-width: 800px)']);
	}

	public function testMissingSrcsetConfigThrowsOnConstruction()
	{
		$this->app(['thumbs' => []]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('No srcset presets found');

		$this->imagex();
	}

	public function testMissingFormatPresetThrowsOnConstruction()
	{
		$this->app(['timnarr.imagex.formats' => ['avif', 'webp', 'png']]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Missing srcset preset(s) for active formats: 'default-png'");

		$this->imagex();
	}

	public function testCompareFormatsWithSingleFormatThrowsOnConstruction()
	{
		$this->app(['timnarr.imagex.formats' => ['webp']]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Not enough formats to determine the smallest');

		$this->imagex(['compareFormats' => true]);
	}

	public function testCompareFormatsRendersOnlyFormatsUpToTheSmallest()
	{
		$this->app([
			'timnarr.imagex.formats' => ['webp'],
			'timnarr.imagex.addOriginalFormatAsSource' => true,
		]);
		$imagex = $this->imagex(['compareFormats' => true]);

		$smallest = $imagex->getSmallestFormatForImage();
		$types = array_column($imagex->getPictureSources(), 'type');

		$this->assertContains($smallest, ['webp', 'originalformat']);
		$this->assertSame($smallest === 'webp' ? ['image/webp', 'image/jpeg'] : ['image/jpeg'], $types);
	}

	public function testRenderingDoesNotCacheSrcsetPresetsPersistently()
	{
		$imagex = $this->imagex(['artDirection' => [['media' => '(min-width: 800px)', 'ratio' => '1/1']]]);
		$imagex->getPictureSources();
		$imagex->getImgAttributes();

		$this->assertSame([], $this->cacheFiles('srcset-config-'));
	}

	public function testCompareFormatsResultIsCachedPersistently()
	{
		$this->app([
			'timnarr.imagex.formats' => ['webp'],
			'timnarr.imagex.addOriginalFormatAsSource' => true,
		]);
		$this->imagex(['compareFormats' => true])->getPictureSources();

		$this->assertCount(1, $this->cacheFiles('compare-formats-'));
	}

	private function cacheFiles(string $prefix): array
	{
		$cacheRoot = App::instance()->root('cache');

		if (!is_dir($cacheRoot)) {
			return [];
		}

		$files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cacheRoot, \FilesystemIterator::SKIP_DOTS));

		return array_values(array_filter(
			array_map(fn ($file) => $file->getFilename(), iterator_to_array($files, false)),
			fn ($name) => str_starts_with($name, $prefix)
		));
	}

	public function testPictureSnippetRenders()
	{
		$html = snippet('imagex-picture', [
			'image' => $this->image(),
			'ratio' => '16/9',
			'artDirection' => [['media' => '(min-width: 800px)', 'ratio' => '1/1']],
		], return: true);

		$this->assertStringContainsString('<picture', $html);
		$this->assertStringContainsString('<source', $html);
		$this->assertMatchesRegularExpression('/<img [^>]*height="225"[^>]*width="400"/', $html);
		$this->assertMatchesRegularExpression('/<img [^>]*id="(imagex-[0-9a-f]{8})"/', $html);

		preg_match('/<img [^>]*id="([^"]+)"/', $html, $matches);
		$this->assertStringContainsString('<style>@media (min-width: 800px) { #' . $matches[1] . ' {', $html);
	}

	public function testJsonSnippetRenders()
	{
		$json = snippet('imagex-picture-json', ['image' => $this->image(), 'ratio' => '16/9'], return: true);
		$data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

		$this->assertSame(400, $data['img']['width']);
		$this->assertSame(225, $data['img']['height']);
		$this->assertCount(2, $data['picture']['sources']);
	}

	public function testSnippetWithOnlyImageUsesClassDefaults()
	{
		$html = snippet('imagex-picture', ['image' => $this->image()], return: true);

		$this->assertMatchesRegularExpression('/<img [^>]*height="300"[^>]*loading="lazy"[^>]*width="400"/', $html);
	}

	public function testSnippetWithoutImageThrowsDescriptiveError()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Missing required option: image');

		snippet('imagex-picture', ['ratio' => '16/9'], return: true);
	}

	public function testFocusUsesStyleAttributeWithoutNonce()
	{
		$imagex = $this->imagex(['focus' => true]);

		$this->assertSame(['object-fit: cover;', 'object-position: center;'], $imagex->getImgAttributes()['style']);
		$this->assertSame('', $imagex->getArtDirectionStyles());
	}

	public function testFocusMovesIntoStylesWithNonce()
	{
		$imagex = $this->imagex(['focus' => true, 'nonce' => 'abc123']);
		$attributes = $imagex->getImgAttributes();

		$this->assertSame([], $attributes['style']);
		$this->assertSame('#' . $attributes['id'] . ' { object-fit: cover; object-position: center; }', $imagex->getArtDirectionStyles());
	}

	public function testNonceWithFocusAndArtDirectionKeepsBaseRuleFirst()
	{
		$css = $this->imagex([
			'focus' => true,
			'nonce' => 'abc123',
			'artDirection' => [['media' => '(min-width: 800px)', 'ratio' => '1/1']],
		])->getArtDirectionStyles();

		$this->assertMatchesRegularExpression('/^#imagex-[0-9a-f]{8} \\{ object-fit: cover; object-position: center; \\} @media \\(min-width: 800px\\)/', $css);
	}

	public function testNonceWithoutFocusOrArtDirectionGeneratesNoStyles()
	{
		$imagex = $this->imagex(['nonce' => 'abc123']);

		$this->assertSame('', $imagex->getArtDirectionStyles());
		$this->assertNull($imagex->getImgAttributes()['id']);
	}

	public function testInvalidNonceThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid option: nonce');

		$this->imagex(['nonce' => '']);
	}

	public function testPictureSnippetAddsNonceToStyleElement()
	{
		$html = snippet('imagex-picture', [
			'image' => $this->image(),
			'focus' => true,
			'nonce' => 'abc123',
		], return: true);

		$this->assertStringContainsString('<style nonce="abc123">#imagex-', $html);
		$this->assertStringNotContainsString('style="', $html);
	}
}
