<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;
use Kirby\Filesystem\Dir;

class ImagexPreloadTest extends ImagexTestCase
{
	/**
	 * Renders a page-like snippet with a head stack, like a real template would.
	 */
	private function renderWithHeadStack(array $imagexData): string
	{
		Dir::make($this->root . '/site/snippets');
		file_put_contents(
			$this->root . '/site/snippets/stack-page.php',
			'<head><?php stack(\'imagex-head\') ?></head><body><?php snippet(\'imagex-picture\', $imagexData) ?></body>'
		);

		return snippet('stack-page', ['imagexData' => ['image' => $this->image(), ...$imagexData]], return: true);
	}

	public function testPreloadLinksAreEmptyWithoutPreload()
	{
		$this->assertSame([], $this->imagex(['loading' => 'eager'])->getPreloadLinks());
	}

	public function testPreloadLinkUsesOnlyTheFirstFormat()
	{
		$links = $this->imagex([
			'loading' => 'eager',
			'preload' => true,
			'attributes' => ['sources' => ['sizes' => '50vw']],
		])->getPreloadLinks();

		$this->assertCount(1, $links);
		$this->assertSame('preload', $links[0]['rel']);
		$this->assertSame('image', $links[0]['as']);
		$this->assertSame('image/avif', $links[0]['type']);
		$this->assertSame('50vw', $links[0]['imagesizes']);
		$this->assertSame('high', $links[0]['fetchpriority']);
		$this->assertNull($links[0]['href']);
		$this->assertNull($links[0]['media']);
		$this->assertStringContainsString('400w', $links[0]['imagesrcset']);
		$this->assertStringContainsString('.avif', $links[0]['imagesrcset']);
	}

	public function testPreloadLinksMakeArtDirectionMediaExclusive()
	{
		$links = $this->imagex([
			'loading' => 'eager',
			'preload' => true,
			'artDirection' => [
				['media' => '(width >= 1200px)', 'ratio' => '21/9'],
				['media' => '(width >= 600px)', 'ratio' => '4/3'],
			],
		])->getPreloadLinks();

		$this->assertSame([
			'(width >= 1200px)',
			'(width >= 600px) and (width < 1200px)',
			'(width < 1200px) and (width < 600px)',
		], array_column($links, 'media'));
		$this->assertSame(['image/avif', 'image/avif', 'image/avif'], array_column($links, 'type'));
	}

	public function testPreloadLinkFollowsCompareFormats()
	{
		$links = $this->pinnedImagex(['image.jpg' => 'webp'], ['loading' => 'eager', 'preload' => true])->getPreloadLinks();

		$this->assertSame(['image/webp'], array_column($links, 'type'));
	}

	public function testPreloadLinksUseTheFormatPictureUsesPerSource()
	{
		// Regression: the art-directed WebP was preloaded although AVIF browsers
		// used the default AVIF source at that width
		$links = $this->pinnedImagex(['image.jpg' => 'avif', 'landscape.jpg' => 'webp'], [
			'loading' => 'eager',
			'preload' => true,
			'artDirection' => [['media' => '(width >= 800px)', 'image' => $this->image('landscape.jpg')]],
		])->getPreloadLinks();

		$this->assertSame(['image/webp', 'image/avif'], array_column($links, 'type'));
		$this->assertSame(['(width >= 800px)', '(width < 800px)'], array_column($links, 'media'));
		$this->assertStringContainsString('landscape', $links[0]['imagesrcset']);
	}

	public function testPreloadLinksSkipUnreachableSourcesWithTheSameMedia()
	{
		$links = $this->imagex([
			'loading' => 'eager',
			'preload' => true,
			'artDirection' => [
				['media' => '(width >= 800px)', 'ratio' => '21/9'],
				['media' => '(width >= 800px)', 'ratio' => '1/1'],
			],
		])->getPreloadLinks();

		$this->assertSame(['(width >= 800px)', '(width < 800px)'], array_column($links, 'media'));
		$this->assertStringContainsString('400x171', $links[0]['imagesrcset']);
	}

	public function testPreloadLinkFallsBackToImgWithoutFormats()
	{
		// Nested: Kirby merges a flat 'timnarr.imagex.formats' => [] with the plugin default
		$this->app(['timnarr.imagex' => ['formats' => []]]);
		$links = $this->imagex([
			'loading' => 'eager',
			'preload' => true,
			'attributes' => ['img' => ['sizes' => '100vw']],
		])->getPreloadLinks();

		$this->assertCount(1, $links);
		$this->assertNull($links[0]['type']);
		$this->assertNull($links[0]['href']);
		$this->assertSame('100vw', $links[0]['imagesizes']);
		$this->assertStringContainsString('800w', $links[0]['imagesrcset']);
	}

	public function testPreloadLinkUsesHrefWithoutImgSrcset()
	{
		$this->app(['timnarr.imagex' => ['formats' => [], 'noSrcsetInImg' => true]]);
		$links = $this->imagex(['loading' => 'eager', 'preload' => true])->getPreloadLinks();

		$this->assertNull($links[0]['imagesrcset']);
		$this->assertStringContainsString('.jpg', $links[0]['href']);
	}

	public function testPreloadWithLazyLoadingThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'preload' requires 'loading' => 'eager'");

		$this->imagex(['preload' => true]);
	}

	public function testNonBooleanPreloadThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid option: preload');

		$this->imagex(['loading' => 'eager', 'preload' => 'yes']);
	}

	public function testPreloadWithMediaTypeThrowsOnConstruction()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Media 'screen and (width >= 800px)' can't be used for preload links");

		$this->imagex([
			'loading' => 'eager',
			'preload' => true,
			'artDirection' => [['media' => 'screen and (width >= 800px)', 'ratio' => '1/1']],
		]);
	}

	public function testInvalidStackOptionThrows()
	{
		$this->app(['timnarr.imagex.stack' => '']);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'timnarr.imagex.stack' must be a stack name");

		$this->imagex();
	}

	public function testWhitespaceStackOptionThrows()
	{
		$this->app(['timnarr.imagex.stack' => '  ']);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'timnarr.imagex.stack' must be a stack name");

		$this->imagex();
	}

	public function testPictureSnippetPushesPreloadLinksAndStylesToStack()
	{
		$this->app(['timnarr.imagex.stack' => 'imagex-head']);
		$html = $this->renderWithHeadStack([
			'ratio' => '16/9',
			'loading' => 'eager',
			'preload' => true,
			'artDirection' => [['media' => '(width >= 800px)', 'ratio' => '1/1']],
		]);

		[$head, $body] = explode('</head>', $html);

		$this->assertSame(2, substr_count($head, '<link '));
		$this->assertStringContainsString('<link as="image" fetchpriority="high" imagesrcset="', $head);
		$this->assertStringContainsString('<style>@media (width >= 800px)', $head);
		$this->assertStringNotContainsString('<style', $body);
		$this->assertStringNotContainsString('kirby-stack', $html);
	}

	public function testPictureSnippetDeduplicatesPreloadLinks()
	{
		$this->app(['timnarr.imagex.stack' => 'imagex-head']);
		Dir::make($this->root . '/site/snippets');
		file_put_contents(
			$this->root . '/site/snippets/stack-page.php',
			'<head><?php stack(\'imagex-head\') ?></head><?php snippet(\'imagex-picture\', $data) ?><?php snippet(\'imagex-picture\', $data) ?>'
		);

		$html = snippet('stack-page', ['data' => ['image' => $this->image(), 'loading' => 'eager', 'preload' => true]], return: true);

		$this->assertSame(1, substr_count($html, '<link '));
	}

	public function testPictureSnippetKeepsStylesInlineWithoutStack()
	{
		$html = $this->renderWithHeadStack([
			'ratio' => '16/9',
			'artDirection' => [['media' => '(width >= 800px)', 'ratio' => '1/1']],
		]);

		$this->assertStringContainsString('<head></head>', $html);
		$this->assertStringContainsString('<style>@media (width >= 800px)', $html);
	}

	public function testPictureSnippetWithPreloadButWithoutStackThrows()
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Option 'preload' needs a template stack");

		snippet('imagex-picture', ['image' => $this->image(), 'loading' => 'eager', 'preload' => true], return: true);
	}

	public function testJsonSnippetIncludesPreloadLinks()
	{
		$json = snippet('imagex-picture-json', ['image' => $this->image(), 'loading' => 'eager', 'preload' => true], return: true);
		$data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

		$this->assertSame('preload', $data['preloadLinks'][0]['rel']);
		$this->assertArrayNotHasKey('href', $data['preloadLinks'][0]);
	}

	public function testJsonSnippetOmitsEmptyPreloadLinks()
	{
		$json = snippet('imagex-picture-json', ['image' => $this->image()], return: true);

		$this->assertArrayNotHasKey('preloadLinks', json_decode($json, true, flags: JSON_THROW_ON_ERROR));
	}
}
