<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PreloadTest extends TestCase
{
	public function testWrapMediaConditionKeepsSingleGroup()
	{
		$this->assertSame('(width >= 800px)', wrapMediaCondition(' (width >= 800px) '));
	}

	public function testWrapMediaConditionWrapsCombinedConditions()
	{
		$this->assertSame('((width >= 800px) and (orientation: landscape))', wrapMediaCondition('(width >= 800px) and (orientation: landscape)'));
		$this->assertSame('(not (orientation: portrait))', wrapMediaCondition('not (orientation: portrait)'));
	}

	public function testExclusiveMediaConditionsExcludeEarlierConditions()
	{
		$this->assertSame([
			'(width >= 1200px)',
			'((width >= 600px) and (orientation: landscape)) and (width < 1200px)',
			'(width < 1200px) and (not ((width >= 600px) and (orientation: landscape)))',
		], exclusiveMediaConditions([
			'(width >= 1200px)',
			'(width >= 600px) and (orientation: landscape)',
			null,
		]));
	}

	public function testExclusiveMediaConditionsKeepKeys()
	{
		$this->assertSame(['a' => '(width >= 800px)', 'default' => '(width < 800px)'], exclusiveMediaConditions(['a' => '(width >= 800px)', 'default' => null]));
	}

	public function testExclusiveMediaConditionsWithoutMediaAlwaysMatch()
	{
		$this->assertSame([null], exclusiveMediaConditions([null]));
		$this->assertSame([], exclusiveMediaConditions([]));
	}

	public function testExclusiveMediaConditionsRejectMediaTypes()
	{
		$this->expectException(InvalidArgumentException::class);

		exclusiveMediaConditions(['screen and (width >= 800px)']);
	}

	public function testExclusiveMediaConditionsRejectQueryLists()
	{
		$this->expectException(InvalidArgumentException::class);

		exclusiveMediaConditions(['(width >= 800px), print']);
	}

	public function testNegateRangeMediaFeatureInvertsRangeSyntax()
	{
		$this->assertSame('(width < 800px)', negateRangeMediaFeature('(width >= 800px)'));
		$this->assertSame('(width <= 800px)', negateRangeMediaFeature('(width > 800px)'));
		$this->assertSame('(height > 40em)', negateRangeMediaFeature(' ( height <= 40em ) '));
		$this->assertSame('(aspect-ratio >= 16/9)', negateRangeMediaFeature('(aspect-ratio < 16/9)'));
	}

	public function testNegateRangeMediaFeatureInvertsValueFirstSyntax()
	{
		// 800px <= width means width >= 800px
		$this->assertSame('(width < 800px)', negateRangeMediaFeature('(800px <= width)'));
		$this->assertSame('(width >= 800px)', negateRangeMediaFeature('(800px > width)'));
	}

	public function testNegateRangeMediaFeatureConvertsLegacySyntax()
	{
		$this->assertSame('(width < 800px)', negateRangeMediaFeature('(min-width: 800px)'));
		$this->assertSame('(width > 800px)', negateRangeMediaFeature('(max-width: 800px)'));
		$this->assertSame('(resolution < 2dppx)', negateRangeMediaFeature('(MIN-RESOLUTION: 2dppx)'));
	}

	public function testNegateRangeMediaFeatureReturnsNullForOtherConditions()
	{
		$this->assertNull(negateRangeMediaFeature('(orientation: landscape)'));
		$this->assertNull(negateRangeMediaFeature('(400px < width < 800px)'));
		$this->assertNull(negateRangeMediaFeature('(width >= 800px) and (orientation: landscape)'));
		$this->assertNull(negateRangeMediaFeature('(prefers-color-scheme: dark)'));
	}

	public function testExclusiveMediaConditionsFallBackToNotForOtherConditions()
	{
		$this->assertSame(
			['(orientation: landscape)', '(not (orientation: landscape))'],
			exclusiveMediaConditions(['(orientation: landscape)', null])
		);
	}

	public function testBuildPreloadLinksUsesFirstSourcePerMedia()
	{
		$links = buildPreloadLinks([
			['media' => '(width >= 800px)', 'type' => 'image/avif', 'srcset' => 'wide.avif 800w', 'sizes' => '100vw'],
			['media' => '(width >= 800px)', 'type' => 'image/webp', 'srcset' => 'wide.webp 800w', 'sizes' => '100vw'],
			['type' => 'image/avif', 'srcset' => 'narrow.avif 400w', 'sizes' => '50vw'],
			['type' => 'image/webp', 'srcset' => 'narrow.webp 400w', 'sizes' => '50vw'],
		], ['src' => 'narrow.jpg', 'srcset' => 'narrow.jpg 400w']);

		$this->assertSame([
			[
				'rel' => 'preload',
				'as' => 'image',
				'href' => null,
				'imagesrcset' => 'wide.avif 800w',
				'imagesizes' => '100vw',
				'media' => '(width >= 800px)',
				'type' => 'image/avif',
				'fetchpriority' => 'high',
			],
			[
				'rel' => 'preload',
				'as' => 'image',
				'href' => null,
				'imagesrcset' => 'narrow.avif 400w',
				'imagesizes' => '50vw',
				'media' => '(width < 800px)',
				'type' => 'image/avif',
				'fetchpriority' => 'high',
			],
		], $links);
	}

	public function testBuildPreloadLinksFallsBackToImgSrcset()
	{
		$links = buildPreloadLinks([], ['src' => 'small.jpg', 'srcset' => 'small.jpg 400w, large.jpg 800w', 'sizes' => '100vw']);

		$this->assertCount(1, $links);
		$this->assertNull($links[0]['href']);
		$this->assertNull($links[0]['media']);
		$this->assertNull($links[0]['type']);
		$this->assertSame('small.jpg 400w, large.jpg 800w', $links[0]['imagesrcset']);
		$this->assertSame('100vw', $links[0]['imagesizes']);
	}

	public function testBuildPreloadLinksUsesImgSrcWithoutSrcset()
	{
		$links = buildPreloadLinks(
			[['media' => '(width >= 800px)', 'type' => 'image/webp', 'srcset' => 'wide.webp 800w']],
			['src' => 'image.svg']
		);

		$this->assertSame('image.svg', $links[1]['href']);
		$this->assertNull($links[1]['imagesrcset']);
		$this->assertSame('(width < 800px)', $links[1]['media']);
	}
}
