<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;

/**
 * Converts 'class' and 'style' string values to arrays in a flat attribute array.
 *
 * 'class' strings are split by whitespace into individual class names.
 * 'style' strings are wrapped in a single-element array to preserve CSS values
 * that contain spaces (e.g. 'background-color: red').
 *
 * @param array $attributes Flat attribute array (not structured by loading mode).
 * @return array Attribute array with 'class' and 'style' coerced to arrays.
 *
 * @internal Not part of the public API — may change in any release.
 */
function coerceClassStyleToArrays(array $attributes): array
{
	if (isset($attributes['class']) && is_string($attributes['class'])) {
		$attributes['class'] = array_values(array_filter(explode(' ', $attributes['class'])));
	}

	if (isset($attributes['style']) && is_string($attributes['style'])) {
		$attributes['style'] = [$attributes['style']];
	}

	return $attributes;
}

/**
 * Validates attribute value types in the options array against expected types.
 *
 * Validates that 'style' and 'class' attributes are provided as arrays.
 * Called by normalizeAttributesStructure() after auto-converting strings, so it
 * catches other unexpected types (e.g. integers) once, at construction.
 *
 * @param array $options Associative array of options with attributes by loading modes ('shared', 'eager', 'lazy').
 * @throws InvalidArgumentException If attribute types do not match expected types.
 *
 * @internal Not part of the public API — may change in any release.
 */
function validateAttributeTypes(array $options): void
{
	$violations = [];

	foreach ($options as $loadingMode => $attributes) {
		foreach ($attributes as $attribute => $value) {
			if (in_array($attribute, ['class', 'style'], true) && !is_array($value)) {
				$violations[] = "attribute \"$attribute\" in \"$loadingMode\" expected to be array, " . gettype($value) . ' given.';
			}
		}
	}

	if (!empty($violations)) {
		throw new InvalidArgumentException('[kirby-imagex] Type mismatch detected: ' . implode(', ', $violations));
	}
}

/**
 * Merges HTML attributes for different loading modes with optional default values.
 *
 * User attributes always override default attributes. Defaults are used as fallback.
 * Extends 'shared' attributes with 'eager' or 'lazy' loading mode-specific attributes.
 *
 * For 'class' and 'style' attributes: Arrays are merged and duplicates removed.
 * For other attributes: New values override existing values.
 *
 * Note: Returns attributes with 'class' and 'style' as arrays. Use transformForJson()
 * to convert them to strings for JSON output.
 *
 * User attributes are expected to come from normalizeAttributesStructure(), which
 * already validated their types; defaults are built by the plugin itself.
 *
 * @param array $attributes User-defined attributes structured by loading modes.
 * @param string $loadingMode The loading mode to merge attributes for ('eager' or 'lazy').
 * @param array $defaultAttributes Optional default attributes to apply as fallback.
 * @return array Merged array of HTML attributes for specified loading mode (class/style as arrays).
 * @throws InvalidArgumentException If $loadingMode is invalid.
 *
 * @internal Not part of the public API — may change in any release.
 */
function mergeHTMLAttributes(array $attributes, string $loadingMode, array $defaultAttributes = ['shared' => [], 'eager' => [], 'lazy' => []]): array
{
	if (!in_array($loadingMode, ['eager', 'lazy'], true)) {
		throw new InvalidArgumentException("[kirby-imagex] Invalid loadingMode: \"$loadingMode\".");
	}

	// 'class' and 'style' are arrays (validated in normalizeAttributesStructure()): merged, deduplicated, empty/null/false entries dropped
	$mergeValues = fn (array $current, array $new) => array_values(array_filter(
		array_unique(array_merge($current, $new)),
		fn ($value) => $value !== '' && $value !== null && $value !== false
	));

	$mergedAttributes = $defaultAttributes['shared'] ?? [];

	// Merge in ascending priority — default loading-mode, user 'shared',
	// then user loading-mode-specific (user attributes always win)
	$priorityLayers = [
		$defaultAttributes[$loadingMode] ?? [],
		$attributes['shared'] ?? [],
		$attributes[$loadingMode] ?? [],
	];

	foreach ($priorityLayers as $layer) {
		foreach ($layer as $attr => $value) {
			$mergedAttributes[$attr] = in_array($attr, ['class', 'style'], true)
				? $mergeValues($mergedAttributes[$attr] ?? [], $value)
				: $value;
		}
	}

	return $mergedAttributes;
}

/**
 * Normalizes user-provided attributes to the internal shared/eager/lazy structure.
 *
 * Converts flat attribute arrays to the shared structure:
 * ['alt' => 'text', 'class' => ['my-class']]
 * becomes
 * ['shared' => ['alt' => 'text', 'class' => ['my-class']], 'eager' => [], 'lazy' => []]
 *
 * If the array already has 'shared', 'eager', or 'lazy' keys, it's returned as-is
 * with missing keys filled in as empty arrays. Mixing both styles throws, since
 * the flat keys would otherwise be dropped silently.
 *
 * 'class' and 'style' strings are auto-converted to arrays:
 * - 'class' => 'foo bar'  becomes  'class' => ['foo', 'bar']
 * - 'style' => 'color: red'  becomes  'style' => ['color: red']
 *
 * @param array $attributes User-provided attributes (flat or structured)
 * @return array Normalized attributes with shared/eager/lazy structure
 * @throws InvalidArgumentException If flat and loading mode keys are mixed, or 'class'/'style' have an invalid type.
 *
 * @internal Not part of the public API — may change in any release.
 */
function normalizeAttributesStructure(array $attributes): array
{
	$loadingModeKeys = ['shared', 'eager', 'lazy'];

	// Check if any loading mode keys exist
	$hasLoadingModeKeys = !empty(array_intersect(array_keys($attributes), $loadingModeKeys));

	if ($hasLoadingModeKeys) {
		$flatKeys = array_diff(array_keys($attributes), $loadingModeKeys);

		if (!empty($flatKeys)) {
			$flatList = implode(', ', array_map(fn ($key) => "'{$key}'", $flatKeys));

			throw new InvalidArgumentException("[kirby-imagex] Attributes mix flat keys ({$flatList}) with loading mode keys ('shared', 'eager', 'lazy'). Move them into 'shared', or pass all attributes flat.");
		}

		// Already structured, just ensure all keys exist and coerce class/style
		$normalized = [
			'shared' => coerceClassStyleToArrays($attributes['shared'] ?? []),
			'eager' => coerceClassStyleToArrays($attributes['eager'] ?? []),
			'lazy' => coerceClassStyleToArrays($attributes['lazy'] ?? []),
		];
	} else {
		// Flat structure - wrap in 'shared'
		$normalized = [
			'shared' => coerceClassStyleToArrays($attributes),
			'eager' => [],
			'lazy' => [],
		];
	}

	validateAttributeTypes($normalized);

	return $normalized;
}
