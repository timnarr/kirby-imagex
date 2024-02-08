<?php

namespace TimNarr;

use Kirby\Cms\File;
use Kirby\Exception\Exception;
use Kirby\Exception\InvalidArgumentException;

/**
 * Calculates the greatest common divisor (GCD) of two numbers using the Euclidean algorithm.
 *
 * @param int $a The first number.
 * @param int $b The second number.
 * @return int The GCD of $a and $b.
 */
function greatestCommonDivisor(int $a, int $b): int
{
	while ($b !== 0) {
		$t = $b;
		$b = $a % $b;
		$a = $t;
	}

	return $a;
}

/**
 * Calculates the aspect ratio from image dimensions using the GCD of width and height.
 *
 * @param int $width Width of the image.
 * @param int $height Height of the image.
 * @return array Associative array with 'x' and 'y' keys for the aspect ratio.
 */
function getAspectRatioFromImage(int $width, int $height): array
{
	$gcd = greatestCommonDivisor($width, $height);
	$ratioX = $width / $gcd;
	$ratioY = $height / $gcd;

	return [
		'x' => (int)$ratioX,
		'y' => (int)$ratioY,
	];
}

/**
 * Get the aspect ratio from a string format "x/y".
 *
 * @param string $ratioString The aspect ratio in string format.
 * @return array Associative array with 'x' and 'y' keys for the aspect ratio.
 * @throws InvalidArgumentException If the format is not "x/y".
 * @throws Exception If either 'x' or 'y' in the ratio is 0.
 */
function getAspectRatioFromRatioString(string $ratioString): array
{

	if (!preg_match('/^\d+\/\d+$/', $ratioString)) {
		throw new InvalidArgumentException('[kirby-imagex] Invalid ratio format. Expected format "x/y".');
	}

	$ratioArray = explode('/', $ratioString);
	$ratioX = (int)$ratioArray[0];
	$ratioY = (int)$ratioArray[1];

	if ($ratioX === 0 || $ratioY === 0) {
		throw new Exception('[kirby-imagex] Invalid ratio format. "x" and "y" must be greater than 0.');
	}

	return [
		'x' => $ratioX,
		'y' => $ratioY,
	];
}

/**
 * Determines the aspect ratio of an image either from image dimensions or a specified ratio string.
 *
 * @param string $ratio The aspect ratio as a string or 'intrinsic' to use the image's dimensions.
 * @param File $image The image file object.
 * @return array Associative array with 'x' and 'y' keys for the aspect ratio.
 */
function getAspectRatio(string $ratio, File $image): array
{
	return $ratio === 'intrinsic'
		? getAspectRatioFromImage($image->width(), $image->height())
		: getAspectRatioFromRatioString($ratio);
}
