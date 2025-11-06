<?php

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\Str;

/**
 * Normalizes an image format.
 *
 * @param string $format The image format.
 * @return string The normalized image format.
 */
function normalizeFormat(string $format): string
{
	return strtolower($format) === 'jpg' ? 'jpeg' : strtolower($format);
}

/**
 * Optionally converts URLs to relative paths based on configuration settings.
 * Only processes internal URLs (URLs that start with the site URL).
 * For srcset strings with multiple URLs, processes each URL individually.
 *
 * @param string $url The URL to process (can be a single URL or srcset string with multiple URLs).
 * @param bool|null $useRelativeUrls Optionally override the default setting for using relative URLs.
 * @param string|null $siteUrl Optionally override the default site URL.
 * @return string The URL, potentially converted to a relative path.
 */
function urlHandler(string $url, bool|null $useRelativeUrls = null, string|null $siteUrl = null): string
{
	$useRelativeUrls = $useRelativeUrls ?? kirby()->option('timnarr.imagex.relativeUrls');
	$siteUrl = $siteUrl ?? site()->url();

	// Only apply relative URL conversion if the option is enabled
	if (!$useRelativeUrls) {
		return $url;
	}

	// Replace all occurrences of the site URL with relative paths
	// This handles both single URLs and srcset strings with multiple URLs
	return Str::replace($url, $siteUrl, '');
}

/**
 * Finds and returns the smallest value in an array and its key.
 *
 * @param array $array The array to search.
 * @return string The smallestKey.
 * @throws InvalidArgumentException If the array is empty.
 */
function findSmallestValueAndKey(array $array): string
{
	if (empty($array)) {
		throw new InvalidArgumentException('[kirby-imagex] Input array cannot be empty.');
	}

	// Find the smallest value in the array
	$smallestValue = min($array);

	// Find the key associated with the smallest value
	$smallestKey = array_search($smallestValue, $array, true);

	return $smallestKey;
}

/**
 * Finds and returns the middle element and its key from an array.
 *
 * @param array $inputArray The array to process.
 * @return array|null Associative array with 'middleKey' and 'middleValue', or null if array is empty.
 * @throws InvalidArgumentException If the array is empty.
 */
function findMiddleArray(array $inputArray): array
{
	if (empty($inputArray)) {
		throw new InvalidArgumentException('[kirby-imagex] Input array cannot be empty.');
	}

	// Calculate the middle index of the array keys
	// For arrays with an even number of elements, the function selects the lower middle element as the "middle" one.
	$keys = array_keys($inputArray);
	$middleIndex = intdiv(count($keys), 2);

	// Get the key corresponding to the middle index
	$middleKey = $keys[$middleIndex];

	// Return the 'middle' element of the array
	return [
		'middleKey' => $middleKey,
		'middleValue' => $inputArray[$middleKey],
	];
}

/**
 * Applies urlHandler to all URL-based attributes in an attributes array.
 * Only processes if relativeUrls option is enabled.
 *
 * @param array $attributes The attributes array to process.
 * @param bool|null $useRelativeUrls Optionally override the relativeUrls setting (primarily for testing).
 * @param string|null $siteUrl Optionally override the site URL (primarily for testing).
 * @return array The processed attributes array with relative URLs where applicable.
 */
function applyUrlHandlerToAttributes(array $attributes, bool|null $useRelativeUrls = null, string|null $siteUrl = null): array
{
	$useRelativeUrls = $useRelativeUrls ?? kirby()->option('timnarr.imagex.relativeUrls');

	// Only process if relativeUrls option is enabled
	if (!$useRelativeUrls) {
		return $attributes;
	}

	// List of URL-based attributes that should be processed
	$urlAttributes = ['src', 'srcset', 'data-src', 'data-srcset'];

	foreach ($urlAttributes as $attr) {
		if (isset($attributes[$attr]) && is_string($attributes[$attr])) {
			$attributes[$attr] = urlHandler($attributes[$attr], $useRelativeUrls, $siteUrl);
		}
	}

	return $attributes;
}

/**
 * Handles the 'src' attribute for an image based on the loading mode and custom lazy loading settings.
 *
 * @param string $src The default source URL for the image.
 * @param array $srcAttributes Array of source attributes by loading mode.
 * @param string $loadingMode The loading mode to use for determining the 'src' attribute.
 * @return string|null The determined 'src' value or null if not applicable.
 */
function srcHandler(string $src, array $srcAttributes, string $loadingMode): string|null
{
	$customLazyloading = kirby()->option('timnarr.imagex.customLazyloading');

	if (isset($srcAttributes[$loadingMode]['src'])) {
		return $srcAttributes[$loadingMode]['src'];
	} elseif (!$customLazyloading) {
		return $src;
	} else {
		return null;
	}
}

/**
 * Transforms data for JSON output by converting class/style arrays to strings
 * and removing null values and empty strings.
 *
 * @param mixed $data The data to transform.
 * @return mixed The transformed data.
 */
function transformForJson(mixed $data): mixed
{
	if (is_array($data)) {
		$result = [];
		foreach ($data as $key => $value) {
			// Convert class and style arrays to strings
			if (($key === 'class' || $key === 'style') && is_array($value)) {
				$value = implode(' ', $value);
			}

			// Recursively transform nested arrays
			$transformed = transformForJson($value);

			// Skip null values and empty strings
			if ($transformed !== null && $transformed !== '') {
				$result[$key] = $transformed;
			}
		}

		return $result;
	}

	return $data;
}
