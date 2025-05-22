<?php

namespace TimNarr;

use Kirby\Cms\File;
use Kirby\Exception\Exception;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;

class Imagex
{
	protected bool $critical;
	protected bool $customLazyloading;
	protected array $formats;
	protected File $image;
	protected array $imgAttributes;
	protected array $pictureAttributes;
	protected string $ratio;
	protected array $sourcesArtDirected;
	protected array $sourcesAttributes;
	protected string $srcsetName;
	protected string $formatSizeHandling;

	/**
	 * Constructor to initialize Imagex with its options.
	 *
	 * @param array $options
	 */
	public function __construct(array $options)
	{
		$this->critical = $options['critical'];
		$this->customLazyloading = kirby()->option('timnarr.imagex.customLazyloading');
		$this->formats = kirby()->option('timnarr.imagex.formats');
		$this->image = $options['image'];
		$this->imgAttributes = $options['imgAttributes'];
		$this->pictureAttributes = $options['pictureAttributes'];
		$this->ratio = $options['ratio'];
		$this->sourcesArtDirected = $options['sourcesArtDirected'];
		$this->sourcesAttributes = $options['sourcesAttributes'];
		$this->srcsetName = $options['srcsetName'];
		$this->formatSizeHandling = $options['formatSizeHandling'];
	}

	/**
	 * Determines the loading mode based on the critical flag.
	 *
	 * @return string The loading mode - 'eager' or 'lazy'
	 */
	private function getLoadingMode(): string
	{
		return $this->critical ? 'eager' : 'lazy';
	}

	/**
	 * Get image formats, ensuring uniqueness and correct naming.
	 *
	 * @return array Array with all formats as strings
	 */
	private function getFormats(): array
	{
		$configFormats = $this->formats;
		$includeInitialFormat = kirby()->option('timnarr.imagex.includeInitialFormat');
		$formats = $includeInitialFormat ? A::append($configFormats, ['initialformat']) : $configFormats;

		$formats = array_unique(array_map(function ($item) {
			return normalizeFormat($item);
		}, $formats));

		// Reindex the array to ensure the keys start from 0
		return array_values($formats);
	}

	/**
	 * Get the file format of a image
	 *
	 * @param File|null $image Optional file object to determine format; defaults to main image.
	 * @return string The image file format.
	 */
	private function getImageFormat(File|null $image = null): string
	{
		$image = $image ?? $this->image;

		return normalizeFormat($image->extension());
	}

	/**
	 * Get the srcset preset by name from the Kirby config.
	 *
	 * @return array Srcset presets for used formats.
	 * @throws Exception If srcset preset is not found.
	 */
	private function getSrcsetPresetFromConfig(): array
	{
		$allSrcsetPresets = kirby()->option('thumbs.srcsets');

		if (!isset($allSrcsetPresets[$this->srcsetName])) {
			throw new Exception("[kirby-imagex] Srcset configuration for '{$this->srcsetName}' not found.");
		}

		$srcsetName = $this->srcsetName;
		$srcsetPreset[$this->getImageFormat()] = $allSrcsetPresets[$srcsetName];

		foreach ($this->getFormats() as $format) {
			if ($format === 'initialformat') {
				$srcsetPreset[$format] = $allSrcsetPresets[$srcsetName];
			} else {
				// Check if specific format configuration exists
				if (!isset($allSrcsetPresets[$srcsetName . '-' . $format])) {
					throw new Exception("[kirby-imagex] Srcset configuration for '{$srcsetName}-{$format}' not found.");
				}

				$srcsetPreset[$format] = $allSrcsetPresets[$srcsetName . '-' . $format];
			}
		}

		return $srcsetPreset;
	}

	/**
	 * Get srcset preset with dynamic heights based on aspect ratio.
	 *
	 * @param string|null $ratio Optional aspect ratio; defaults to object's ratio.
	 * @param File|null $image Optional file object; defaults to main image.
	 * @return array Srcset preset with dynamic heights.
	 */
	private function getDynamicSrcsetPreset(string|null $ratio = null, File|null $image = null): array
	{
		$srcsetPreset = $this->getSrcsetPresetFromConfig();
		['x' => $ratioX, 'y' => $ratioY] = getAspectRatio($ratio ?? $this->ratio, $image ?? $this->image);

		// Cache settings
		$version = kirby()->plugin('timnarr/imagex')->version();
		$cache = kirby()->cache('timnarr.imagex');
		$cacheId = 'srcset-config-' . hash('xxh3', $version . $ratioX . $ratioY . json_encode($srcsetPreset));

		// Get srcsetPreset from cache or set it
		$data = $cache->getOrSet($cacheId, function () use ($srcsetPreset, $ratioX, $ratioY) {
			return addRatioBasedHeightToSrcsetPreset($srcsetPreset, $ratioX, $ratioY);
		});

		return $data;
	}

	/**
	 * Get the srcset value for a given srcset preset.
	 *
	 * @param array $srcsetPreset Srcset preset array.
	 * @param File|null $image Optional file object; defaults to main image.
	 * @return string Srcset value string.
	 */
	private function getSrcsetValue(array $srcsetPreset, File|null $image = null): string
	{
		$image = $image ?? $this->image;

		return $image->srcset($srcsetPreset);
	}

	/**
	 * Get the smallest image format based on file size.
	 *
	 * @return string|null Format of the smallest format or null if unable to determine.
	 * @throws Exception If not enough formats are provided for comparison.
	 */
	public function getSmallestFormat(): string|null
	{
		$formats = $this->getFormats();
		$formatsCount = A::count($formats);
		$formatSizeHandling = $this->formatSizeHandling;
		$includeInitialFormat = kirby()->option('timnarr.imagex.includeInitialFormat');

		// Throw an exception if formatSizeHandling is active and there are one or less formats
		if ($formatSizeHandling && $formatsCount <= 1) {
			throw new Exception('[kirby-imagex] Not enough formats to determine the smallest. Please set "formatSizeHandling" to false or add at least two formats in the configuration.');
		}

		// Check for the specific condition where only the 'initialformat' is present and includeInitialFormat is true.
		if (!$formatSizeHandling && $formatsCount === 1 && A::has($formats, 'initialformat') && $includeInitialFormat) {
			return null;
		}

		// Return the first format if there is only one format, regardless of formatSizeHandling's state.
		if (!$formatSizeHandling || $formatsCount === 1) {
			return A::first($formats);
		}

		$image = $this->image;
		$srcsets = $this->getDynamicSrcsetPreset();
		$formatSizes = [];

		foreach ($formats as $format) {
			if (!isset($srcsets[$format])) {
				throw new Exception("[kirby-imagex] No srcset configurations found for format: {$format}");
			}

			$midSizedsrcsetValue = findMiddleArray($srcsets[$format])['middleValue'];
			$formatSizes[$format] = $image->thumb($midSizedsrcsetValue)->size();
		}

		// Find the format with the smallest size
		$smallestFormat = findSmallestValueAndKey($formatSizes);

		return $smallestFormat;
	}

	/**
	 * Get the <img> tag attributes based on srcset preset and user defined + default attributes.
	 *
	 * @return array Attributes for the <img> tag.
	 */
	public function getImgAttributes(): array
	{
		$format = $this->getImageFormat();
		$srcsetPreset = $this->getDynamicSrcsetPreset();
		$srcsetValue = $this->getSrcsetValue($srcsetPreset[$format]);

		$image = $this->image;
		$isCritical = $this->critical;
		$userAttributes = $this->imgAttributes;
		$customLazyloading = $this->customLazyloading;
		$useNoSrcsetInImg = kirby()->option('timnarr.imagex.noSrcsetInImg');

		$firstItemInSrcsetConfig = A::first($srcsetPreset[$format]);
		$src = $image->thumb($firstItemInSrcsetConfig)->url();
		['width' => $width, 'height' => $height] = $firstItemInSrcsetConfig;

		$defaultAttributes = [
			'shared' => [
				'src' => srcHandler($src, $userAttributes, 'shared'),
				'width' => $width,
				'height' => $height,
				'decoding' => 'async',
				'fetchpriority' => $isCritical ? 'high' : null,
			],
			'eager' => [
				'src' => srcHandler($src, $userAttributes, 'eager'),
				'srcset' => $useNoSrcsetInImg ? null : urlHandler($srcsetValue),
			],
			'lazy' => [
				'loading' => $customLazyloading ? null : ($isCritical ? null : 'lazy'),
				'data-src' => $customLazyloading ? urlHandler($src) : null,
				'src' => srcHandler($src, $userAttributes, 'lazy'),
				'data-srcset' => $useNoSrcsetInImg ? null : ($customLazyloading ? urlHandler($srcsetValue) : null),
				'srcset' => $useNoSrcsetInImg ? null : (!$customLazyloading ? urlHandler($srcsetValue) : null),
			],
		];

		return mergeHTMLAttributes($userAttributes, $this->getLoadingMode(), $defaultAttributes);
	}

	/**
	 * Get HTML attributes for the <picture> element based on loading mode and user defined attributes.
	 *
	 * @return array HTML attributes for the <picture> element.
	 */
	public function getPictureAttributes(): array
	{
		return mergeHTMLAttributes($this->pictureAttributes, $this->getLoadingMode());
	}

	/**
	 * Get HTML attributes for a <source> element within a <picture>, including responsive and art direction settings.
	 *
	 * @param string $format Image format for the source.
	 * @param string $srcsetValue Srcset definition string.
	 * @param array $srcsetPreset Srcset configuration array.
	 * @param array $source Additional source settings for art direction.
	 * @return array HTML attributes for the source element.
	 */
	private function getSourceAttributes(string $format, string $srcsetValue, array $srcsetPreset, array $source = []): array
	{
		['width' => $width, 'height' => $height] = A::first($srcsetPreset[$format]);

		if ($format === 'initialformat') {
			$image = $source['image'] ?? $this->image;
			$format = $this->getImageFormat($image);
		}

		$customLazyloading = $this->customLazyloading;
		$defaultAttributes = [
			'shared' => [
				'type' => F::extensionToMime($format),
				'width' => $width,
				'height' => $height,
				'media' => $source['media'] ?? null,
				...($this->sourcesAttributes['shared'] ?? []),
			],
			'eager' => [
				'srcset' => urlHandler($srcsetValue),
				...($this->sourcesAttributes['eager'] ?? []),
			],
			'lazy' => [
				'srcset' => $customLazyloading ? null : urlHandler($srcsetValue),
				'data-srcset' => $customLazyloading ? urlHandler($srcsetValue) : null,
				...($this->sourcesAttributes['lazy'] ?? []),
			],
		];

		return mergeHTMLAttributes($source['attributes'] ?? [], $this->getLoadingMode(), $defaultAttributes);
	}

	/**
	 * Get art-directed picture sources for a specific format.
	 *
	 * @param string $format Image format.
	 * @return array HTML attributes for art-directed picture sources.
	 */
	private function getArtDirectedSourcesPerFormat(string $format): array
	{
		$sources = [];

		foreach ($this->sourcesArtDirected as $source) {
			$sourceRatio = $source['ratio'] ?? 'intrinsic';
			$sourceImage = $source['image'] ?? null;

			$srcsetPreset = $this->getDynamicSrcsetPreset($sourceRatio, $sourceImage);
			$srcsetValue = $this->getSrcsetValue($srcsetPreset[$format], $sourceImage);
			$sourceAttributes = $this->getSourceAttributes($format, $srcsetValue, $srcsetPreset, $source);

			$sources[] = $sourceAttributes;
		}

		return $sources;
	}

	/**
	 * Get default picture sources for a specific format.
	 *
	 * @param string $format Image format.
	 * @return array HTML attributes for default picture sources.
	 */
	private function getDefaultSourcesPerFormat(string $format): array
	{
		$srcsetPreset = $this->getDynamicSrcsetPreset();
		$srcsetValue = $this->getSrcsetValue($srcsetPreset[$format]);
		$attributes = $this->getSourceAttributes($format, $srcsetValue, $srcsetPreset);

		return $attributes;
	}

	/**
	 * Get all picture sources, including both art-directed and default sources for formats.
	 *
	 * @return array Compiled data of all picture sources.
	 */
	public function getPictureSources(): array
	{
		$formats = $this->getFormats();
		$smallestFormat = $this->getSmallestFormat();
		$sources = [];

		for ($i = 0; $i < count($formats); $i++) {
			$format = $formats[$i];

			// If formatSizeHandling is true, skip the current format if the next format is smaller
			if ($smallestFormat && isset($formats[$i + 1])) {
				$nextFormat = $formats[$i + 1];

				if ($smallestFormat === $nextFormat) {
					continue;
				}
			}

			if (!empty($this->sourcesArtDirected)) {
				$sources = array_merge($sources, $this->getArtDirectedSourcesPerFormat($format));
			}

			$sources[] = $this->getDefaultSourcesPerFormat($format);
		}

		return $sources;
	}
}
