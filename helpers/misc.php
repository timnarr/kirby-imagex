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
 *
 * @param string $url The URL to process.
 * @param bool|null $useRelativeUrls Optionally override the default setting for using relative URLs.
 * @param string|null $siteUrl Optionally override the default site URL.
 * @return string The URL, potentially converted to a relative path.
 */
function urlHandler(string $url, bool|null $useRelativeUrls = null, string|null $siteUrl = null): string
{
	$useRelativeUrls = $useRelativeUrls ?? kirby()->option('timnarr.imagex.relativeUrls');
	$siteUrl = $siteUrl ?? site()->url();

	return $useRelativeUrls ? Str::replace($url, $siteUrl, '') : $url;
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
		$srcValue = urlHandler($srcAttributes[$loadingMode]['src']);
	} elseif (!$customLazyloading) {
		$srcValue = urlHandler($src);
	} else {
		$srcValue = null;
	}

	return $srcValue;
}
