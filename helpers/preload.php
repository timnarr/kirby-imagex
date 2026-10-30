<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;

/**
 * Wraps a media condition in parentheses, unless it already is a single
 * parenthesized group (e.g. '(width >= 800px)'), so it can be combined
 * with `and`/`not` without changing its meaning.
 *
 * @param string $media A media condition, e.g. '(width >= 800px) and (orientation: landscape)'.
 * @return string The media condition as one parenthesized group.
 *
 * @internal Not part of the public API — may change in any release.
 */
function wrapMediaCondition(string $media): string
{
	$media = trim($media);

	// Doesn't start with a group, e.g. 'not (orientation: portrait)'
	if (!str_starts_with($media, '(')) {
		return "({$media})";
	}

	$depth = 0;

	foreach (str_split($media) as $index => $char) {
		$depth += match ($char) {
			'(' => 1,
			')' => -1,
			default => 0,
		};

		// The first group closes before the end: the condition consists of several parts
		if ($depth === 0) {
			return $index === strlen($media) - 1 ? $media : "({$media})";
		}
	}

	return "({$media})";
}

/**
 * Negates a single media feature using the Media Queries Level 4 range syntax,
 * e.g. '(width >= 800px)' or '(min-width: 800px)' → '(width < 800px)'.
 *
 * The result reads like the condition it replaces, and avoids nesting `not`
 * inside `and`, which only Media Queries Level 4 supports as well.
 *
 * @param string $condition A media condition.
 * @return string|null The negated condition, or null if it isn't a single range feature
 *                     (e.g. '(orientation: landscape)' or '(400px < width < 800px)').
 *
 * @internal Not part of the public API — may change in any release.
 */
function negateRangeMediaFeature(string $condition): string|null
{
	$features = 'width|height|aspect-ratio|resolution|device-width|device-height|device-aspect-ratio|color|color-index|monochrome';
	$value = '[^()<>=:]+?';
	$inverse = ['>=' => '<', '>' => '<=', '<=' => '>', '<' => '>='];
	$range = fn (string $feature, string $operator, string $featureValue) => '(' . strtolower($feature) . " {$operator} {$featureValue})";
	$condition = trim($condition);

	// Legacy prefixed syntax: (min-width: 800px) / (max-width: 800px)
	if (preg_match("/^\(\s*(min|max)-({$features})\s*:\s*({$value})\s*\)$/i", $condition, $m)) {
		return $range($m[2], strtolower($m[1]) === 'min' ? '<' : '>', $m[3]);
	}

	// Range syntax, feature first: (width >= 800px)
	if (preg_match("/^\(\s*({$features})\s*(>=|<=|>|<)\s*({$value})\s*\)$/i", $condition, $m)) {
		return $range($m[1], $inverse[$m[2]], $m[3]);
	}

	// Range syntax, value first: (800px <= width) means width >= 800px
	if (preg_match("/^\(\s*({$value})\s*(>=|<=|>|<)\s*({$features})\s*\)$/i", $condition, $m)) {
		$flipped = ['>=' => '<=', '>' => '<', '<=' => '>=', '<' => '>'][$m[2]];

		return $range($m[3], $inverse[$flipped], $m[1]);
	}

	return null;
}

/**
 * Makes media conditions mutually exclusive, mirroring how <picture> uses the
 * first matching <source>: each condition additionally requires that none of
 * the earlier ones matches. Preload links can't express "first match wins" —
 * every link with a matching `media` is fetched — so this keeps them from
 * preloading more than one image.
 *
 * A null entry (a source without `media`, i.e. the default) matches whenever
 * no earlier condition does.
 *
 * Earlier conditions are negated in range syntax where possible
 * ('(width >= 800px)' → '(width < 800px)', see negateRangeMediaFeature()),
 * otherwise with `not`.
 *
 * Only media conditions are supported (e.g. '(width >= 800px)' or
 * 'not (orientation: portrait)'): media types ('screen and …') and query
 * lists ('(…), (…)') can't be negated within a condition.
 *
 * @param array<string|null> $mediaList Media conditions in source order.
 * @return array<string|null> The exclusive conditions, same keys; null if a source always matches.
 * @throws InvalidArgumentException If an entry is not a media condition.
 *
 * @internal Not part of the public API — may change in any release.
 */
function exclusiveMediaConditions(array $mediaList): array
{
	$previous = [];
	$result = [];

	foreach ($mediaList as $key => $media) {
		if ($media !== null && (str_contains($media, ',') || !preg_match('/^\s*(\(|not\s*\()/i', $media))) {
			throw new InvalidArgumentException("[kirby-imagex] Media '{$media}' can't be used for preload links: only media conditions like '(width >= 800px)' are supported, no media types ('screen and …') or comma-separated lists.");
		}

		$parts = array_map(fn (string $condition) => negateRangeMediaFeature($condition) ?? "(not {$condition})", $previous);

		if ($media !== null) {
			$wrapped = wrapMediaCondition($media);
			// Only combined with other conditions it needs its own parentheses
			array_unshift($parts, $parts === [] ? trim($media) : $wrapped);
			$previous[] = $wrapped;
		}

		$result[$key] = $parts === [] ? null : implode(' and ', $parts);
	}

	return $result;
}

/**
 * Builds the attributes of the `<link rel="preload">` elements for a <picture>.
 *
 * Preload links can't pick the first supported format like <picture> does: a
 * browser fetches every link whose `type` it supports. So each source gets one
 * link, in its first format, which is what a browser supporting the most modern
 * format uses (honoring compareFormats). Browsers without support for it skip
 * the link.
 *
 * Art-directed sources get mutually exclusive `media` conditions (see
 * exclusiveMediaConditions()), so only the image <picture> uses is fetched.
 * `imagesizes` is the source's own `sizes`: a <source> doesn't inherit the
 * <img>'s `sizes`, and the link must pick the same candidate as <picture>.
 *
 * Without a default <source> (no formats, or an image Kirby can't resize),
 * the default link uses the <img>'s srcset. There's no `href`, unless the <img>
 * has no srcset (`noSrcsetInImg`, or an image Kirby can't resize): browsers
 * without `imagesrcset` support then skip the link instead of fetching a
 * possibly unused image.
 *
 * @param array $pictureSources The <source> attributes in <picture> order.
 * @param array $imgAttributes The <img> attributes.
 * @return array List of attribute arrays, one per link.
 * @throws InvalidArgumentException If a `media` isn't a media condition (see exclusiveMediaConditions()).
 *
 * @internal Not part of the public API — may change in any release.
 */
function buildPreloadLinks(array $pictureSources, array $imgAttributes): array
{
	// Mirror <picture>: per `media`, the first <source> wins (its most modern format).
	// `media` is validated to be non-empty, so '' keys the default (no `media`).
	$sources = [];

	foreach ($pictureSources as $source) {
		$sources[$source['media'] ?? ''] ??= $source;
	}

	// No default <source> (no formats, or an image Kirby can't resize): use the <img>
	$sources[''] ??= [
		'srcset' => $imgAttributes['srcset'] ?? null,
		'sizes' => $imgAttributes['sizes'] ?? null,
		'href' => isset($imgAttributes['srcset']) ? null : $imgAttributes['src'],
	];

	$media = exclusiveMediaConditions(array_map(fn (array $source) => $source['media'] ?? null, $sources));

	$links = [];

	foreach ($sources as $key => $source) {
		$links[] = [
			'rel' => 'preload',
			'as' => 'image',
			'href' => $source['href'] ?? null,
			'imagesrcset' => $source['srcset'] ?? null,
			'imagesizes' => $source['sizes'] ?? null,
			'media' => $media[$key],
			'type' => $source['type'] ?? null,
			'fetchpriority' => 'high',
		];
	}

	return $links;
}
