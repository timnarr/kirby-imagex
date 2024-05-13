<?php

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;

/**
 * Validates attribute value types in the options array against expected types.
 *
 * @param array $options Associative array of options with attributes by loading modes ('shared', 'eager', 'lazy').
 * @throws InvalidArgumentException If attribute types do not match expected types.
 */
function validateAttributeTypes(array $options): void
{
	$expectedTypes = [
		// Attribute as key and expected type as value
		'style' => 'array',
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
 * Validates the attributes array against a set of disallowed attributes for each loading mode.
 *
 * @param array $options The attributes array to validate
 * @throws InvalidArgumentException If disallowed attributes are detected in any loading mode.
 */
function validateAttributes(array $options): void
{
	$disallowedAttributes = [
		'shared' => ['type', 'src', 'data-src', 'srcset', 'data-srcset', 'loading', 'width', 'height'],
		'eager' => ['srcset', 'data-srcset', 'loading'],
		'lazy' => ['data-srcset', 'loading'],
	];

	$violations = [];

	foreach ($options as $loadingMode => $attributes) {
		// Check if there are disallowed attributes defined for the current loading mode
		if (!isset($disallowedAttributes[$loadingMode])) {
			continue;
		}

		foreach ($attributes as $attribute => $value) {
			// Check if the attribute is disallowed in the current loadingMode
			if (in_array($attribute, $disallowedAttributes[$loadingMode])) {
				$violations[] = "attribute \"$attribute\" in \"$loadingMode\"";
			}
		}
	}

	if (!empty($violations)) {
		throw new InvalidArgumentException('[kirby-imagex] Disallowed attributes detected: ' . implode(', ', $violations) . '.');
	}
}

/**
 * Merges HTML attributes for different loading modes with optional default values.
 *
 * Extend 'shared' by 'eager' or 'lazy' loading mode attributes.
 *
 * @param array $options Attributes structured by loading modes.
 * @param string $loadingMode The loading mode to merge attributes for.
 * @param array $defaultOptions Optional default attributes to apply before merging.
 * @return array Merged array of HTML attributes for specified loading mode.
 * @throws InvalidArgumentException If $loadingMode is invalid or missing.
 */
function mergeHTMLAttributes(array $attributes, string $loadingMode, array $defaultAttributes = ['shared' => [], 'eager' => [], 'lazy' => []]): array
{
	validateAttributeTypes($defaultAttributes);
	validateAttributeTypes($attributes);

	validateAttributes($attributes);

	if (!in_array($loadingMode, ['shared', 'eager', 'lazy'])) {
		throw new InvalidArgumentException("[kirby-imagex] Invalid loadingMode: \"$loadingMode\".");
	}

	if (!isset($attributes[$loadingMode]) && !isset($defaultAttributes[$loadingMode])) {
		throw new InvalidArgumentException("[kirby-imagex] LoadingMode \"$loadingMode\" not found in attributes or defaultAttributes.");
	}

	$mergableAttributes = ['class', 'style'];
	$mergedAttributes = $defaultAttributes['shared'] ?? [];

	// Function to merge attributes, handling both array and string values
	$mergeAttributeValues = function ($key, $currentValue, $newValue) use ($mergableAttributes) {
		if (in_array($key, $mergableAttributes)) {
			// Ensure both values are arrays
			$currentValues = is_array($currentValue) ? $currentValue : explode(' ', $currentValue);
			$newValues = is_array($newValue) ? $newValue : explode(' ', $newValue);
			// Merge, remove duplicates, re-index and remove empty values
			$mergedValues = array_filter(array_values(array_unique(array_merge($currentValues, $newValues))));

			return $mergedValues;
		} else {
			// For non-mergable attributes, new value overrides
			return $newValue;
		}
	};

	// Merge default loading mode-specific attributes
	if (isset($defaultAttributes[$loadingMode])) {
		foreach ($defaultAttributes[$loadingMode] as $attr => $value) {
			$mergedAttributes[$attr] = $mergeAttributeValues($attr, $mergedAttributes[$attr] ?? '', $value);
		}
	}

	// Merge shared source attributes
	if (isset($attributes['shared'])) {
		foreach ($attributes['shared'] as $attr => $value) {
			$mergedAttributes[$attr] = $mergeAttributeValues($attr, $mergedAttributes[$attr] ?? '', $value);
		}
	}

	// Merge loading mode-specific source attributes
	if (isset($attributes[$loadingMode])) {
		foreach ($attributes[$loadingMode] as $attr => $value) {
			$mergedAttributes[$attr] = $mergeAttributeValues($attr, $mergedAttributes[$attr] ?? '', $value);
		}
	}

	return $mergedAttributes;
}
