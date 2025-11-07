<?php

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;

/**
 * Validates attribute value types in the options array against expected types.
 *
 * Currently validates that 'style' and 'class' attributes are provided as arrays.
 * These arrays will be merged during processing and converted to strings for output.
 *
 * @param array $options Associative array of options with attributes by loading modes ('shared', 'eager', 'lazy').
 * @throws InvalidArgumentException If attribute types do not match expected types.
 */
function validateAttributeTypes(array $options): void
{
	$expectedTypes = [
		// Attribute as key and expected type as value
		'style' => 'array',
		'class' => 'array',
	];

	$violations = [];

	foreach ($options as $loadingMode => $attributes) {
		foreach ($attributes as $attribute => $value) {
			// Check if the attribute has a defined expected type
			if (isset($expectedTypes[$attribute])) {
				$expectedType = $expectedTypes[$attribute];
				// Validate the type of the attribute's value against the expected type
				$actualType = gettype($value);
				if ($actualType !== $expectedType) {
					$violations[] = "attribute \"$attribute\" in \"$loadingMode\" expected to be $expectedType, $actualType given.";
				}
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
 * @param array $attributes User-defined attributes structured by loading modes.
 * @param string $loadingMode The loading mode to merge attributes for ('shared', 'eager', or 'lazy').
 * @param array $defaultAttributes Optional default attributes to apply as fallback.
 * @return array Merged array of HTML attributes for specified loading mode (class/style as arrays).
 * @throws InvalidArgumentException If $loadingMode is invalid or missing.
 */
function mergeHTMLAttributes(array $attributes, string $loadingMode, array $defaultAttributes = ['shared' => [], 'eager' => [], 'lazy' => []]): array
{
	validateAttributeTypes($defaultAttributes);
	validateAttributeTypes($attributes);

	if (!in_array($loadingMode, ['shared', 'eager', 'lazy'])) {
		throw new InvalidArgumentException("[kirby-imagex] Invalid loadingMode: \"$loadingMode\".");
	}

	if (!isset($attributes[$loadingMode]) && !isset($defaultAttributes[$loadingMode])) {
		throw new InvalidArgumentException("[kirby-imagex] LoadingMode \"$loadingMode\" not found in attributes or defaultAttributes.");
	}

	$mergableAttributes = ['class', 'style'];
	$mergedAttributes = [];

	// Function to merge attributes, handling both array and string values
	$mergeAttributeValues = function ($key, $currentValue, $newValue) use ($mergableAttributes) {
		if (in_array($key, $mergableAttributes)) {
			// Ensure both values are arrays
			$currentValues = is_array($currentValue) ? $currentValue : explode(' ', $currentValue);
			$newValues = is_array($newValue) ? $newValue : explode(' ', $newValue);
			// Merge, remove duplicates, and remove empty strings (but keep null values)
			$merged = array_unique(array_merge($currentValues, $newValues));
			$filtered = array_filter($merged, fn ($val) => $val !== '' && $val !== null && $val !== false);
			// Re-index array to ensure sequential keys starting from 0
			$mergedValues = array_values($filtered);

			return $mergedValues;
		} else {
			// For non-mergable attributes, new value overrides
			return $newValue;
		}
	};

	// Step 1: Start with default 'shared' attributes
	if (isset($defaultAttributes['shared'])) {
		foreach ($defaultAttributes['shared'] as $attr => $value) {
			$mergedAttributes[$attr] = $value;
		}
	}

	// Step 2: Merge default loading mode-specific attributes
	if (isset($defaultAttributes[$loadingMode])) {
		foreach ($defaultAttributes[$loadingMode] as $attr => $value) {
			$mergedAttributes[$attr] = $mergeAttributeValues($attr, $mergedAttributes[$attr] ?? '', $value);
		}
	}

	// Step 3: Merge/override with user 'shared' attributes (user attributes have priority)
	if (isset($attributes['shared'])) {
		foreach ($attributes['shared'] as $attr => $value) {
			$mergedAttributes[$attr] = $mergeAttributeValues($attr, $mergedAttributes[$attr] ?? '', $value);
		}
	}

	// Step 4: Merge/override with user loading mode-specific attributes (highest priority)
	if (isset($attributes[$loadingMode])) {
		foreach ($attributes[$loadingMode] as $attr => $value) {
			$mergedAttributes[$attr] = $mergeAttributeValues($attr, $mergedAttributes[$attr] ?? '', $value);
		}
	}

	return $mergedAttributes;
}
