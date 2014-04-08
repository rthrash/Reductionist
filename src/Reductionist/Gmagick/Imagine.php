<?php
namespace Reductionist\Gmagick;

use Imagine\Gmagick\Image;
use Reductionist\Image\RImagineInterface;

use Imagine\Image\BoxInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Palette\Grayscale;
use Imagine\Image\Palette\CMYK;
// use Imagine\Image\Palette\Color\CMYK as CMYKColor;
// use Imagine\Exception\InvalidArgumentException;
use Imagine\Exception\RuntimeException;

/**
 * Imagine implementation using the Gmagick PHP extension
 */
class Imagine implements ImagineInterface, RImagineInterface
{
	/**
	 * @throws RuntimeException
	 */
	public function __construct()
	{
		if (!class_exists('Gmagick')) {
			throw new RuntimeException('Gmagick not installed');
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function open($path) {
		try {
			$gmagick = new \Gmagick($path);
			$image = new Image($gmagick, $this->createPalette($gmagick));
		}
		catch (\GmagickException $e) {
			throw new RuntimeException("Unable to open image $path", $e->getCode(), $e);
		}

		return $image;
	}

	/**
	 * {@inheritdoc}
	 */
	public function create(BoxInterface $size, ColorInterface $color = null)
	{
		$width = $size->getWidth();
		$height = $size->getHeight();

		$palette = null !== $color ? $color->getPalette() : new RGB();
		$color = null !== $color ? $color : $palette->color('fff');

		try {
			$gmagick = new \Gmagick();

			// Gmagick does not support creation of CMYK GmagickPixel
			// see https://bugs.php.net/bug.php?id=64466
			if ($color instanceof CMYKColor) {
				$switchPalette = $palette;
				$palette = new RGB();
				$pixel   = new \GmagickPixel($palette->color((string) $color));
			} else {
				$switchPalette = null;
				$pixel   = new \GmagickPixel((string) $color);
			}

			if ($color->getAlpha() > 0) {
				// TODO: implement support for transparent background
				throw new RuntimeException('alpha transparency not implemented');
			}

			$gmagick->newimage($width, $height, $pixel->getcolor(false));
			$gmagick->setimagecolorspace(\Gmagick::COLORSPACE_TRANSPARENT);
			// this is needed to propagate transparency
			$gmagick->setimagebackgroundcolor($pixel);

			$image = new Image($gmagick, $palette);

			if ($switchPalette) {
				$image->usePalette($switchPalette);
			}

			return $image;
		} catch (\GmagickException $e) {
			throw new RuntimeException(
				'Could not create empty image', $e->getCode(), $e
			);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function load($string)
	{
		try {
			$gmagick = new \Gmagick();
			$gmagick->readimageblob($string);
		} catch (\GmagickException $e) {
			throw new RuntimeException(
				'Could not load image from string', $e->getCode(), $e
			);
		}

		return new Image($gmagick, $this->createPalette($gmagick));
	}

	/**
	 * {@inheritdoc}
	 */
	public function read($resource)
	{
		if (!is_resource($resource)) {
			throw new InvalidArgumentException('Variable does not contain a stream resource');
		}

		$content = stream_get_contents($resource);

		if (false === $content) {
			throw new InvalidArgumentException('Couldn\'t read given resource');
		}

		return $this->load($content);
	}

	/**
	 * {@inheritdoc}
	 */
	public function font($file, $size, ColorInterface $color)
	{
		$gmagick = new \Gmagick();

		$gmagick->newimage(1, 1, 'transparent');

		return new Font($gmagick, $file, $size, $color);
	}

	private function createPalette(\Gmagick $gmagick)
	{
		$cs = $gmagick->getimagecolorspace();
		if ($cs === \Gmagick::COLORSPACE_SRGB || $cs === \Gmagick::COLORSPACE_RGB)
			return new RGB();
		elseif ($cs === \Gmagick::COLORSPACE_CMYK)
			return new CMYK();
		elseif ($cs === \Gmagick::COLORSPACE_GRAY)
			return new Grayscale();

		throw new RuntimeException(
			'Only RGB, CMYK and Grayscale colorspaces are curently supported'
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function Ropenjpg($path, BoxInterface $size) {
		try {
			$gmagick = new \Gmagick();
			$gmagick->setSize($size->getWidth(), $size->getHeight());
			$gmagick->readImage($path);
		} catch (\GmagickException $e) {
			throw new RuntimeException("Unable to open image $path", $e->getCode(), $e);
		}

		return new Image($gmagick, $this->createPalette($gmagick));
	}
}
