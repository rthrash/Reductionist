<?php
namespace Reductionist\Gmagick;

use Imagine\Gmagick\Imagine;
use Imagine\Gmagick\Image;

use Imagine\Image\BoxInterface;
use Imagine\Image\Box;
use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Palette\RGB;


class RImagine
{
	protected $imagine;
	protected $nullBox;
	protected $emptyBag;
	protected $rgb;


	public function __construct() {
		$this->imagine = new Imagine();
		$this->nullBox = new Box(1, 1);
		$this->emptyBag = new MetadataBag();
		$this->rgb = new RGB();
	}


	public function __call($method, $args) {
		return call_user_func_array(array($this->imagine, $method), $args);
	}


	public function open($path) {
		return $this->Ropen($path, $this->nullBox);
	}


	public function Ropen($path, BoxInterface $size) {
		if (!is_readable($path)) {
			throw new InvalidArgumentException(sprintf('File %s is not readable', $path));
		}

		try {
			$magick = new \Gmagick();
			if ($size !== $this->nullBox) {
				$magick->setSize($size->getWidth(), $size->getHeight());
			}
			$magick->readimage($path);
		}
		catch (\Exception $e) {
			throw new \Imagine\Exception\RuntimeException("Gmagick: Unable to open image $path. {$e->getMessage()}", $e->getCode(), $e);
		}

		return new Image($magick, $this->createPalette($magick), $this->emptyBag);
	}


	public function create(BoxInterface $size, ColorInterface $color = null) {
		$width  = $size->getWidth();
		$height = $size->getHeight();

		if ($color === null) {
			$palette = $this->rgb;
			$color = $palette->color('fff');
		}
		else { $palette = $color->getPalette(); }

		try {
			$pixel = new \GmagickPixel((string) $color);
			$pixel->setColorValue(\Gmagick::COLOR_OPACITY, $color->getAlpha() / 100);  // does nothing as of Gmagick 1.1.7RC2.  Background will be fully opaque.

			$magick = new \Gmagick();
			$magick->newImage($width, $height, $pixel->getcolor(false));
			$magick->setimagecolorspace(\Gmagick::COLORSPACE_TRANSPARENT);
			$magick->setImageBackgroundColor($pixel);

			return new Image($magick, $palette, $this->emptyBag);
		}
		catch (\Exception $e) {
			throw new \Imagine\Exception\RuntimeException('Gmagick: could not create empty image. ' . $e->getMessage(), $e->getCode(), $e);
		}
	}


	public function getImagine() {
		return $this->imagine;
	}


	private function createPalette(\Gmagick $magick) {
		$cs = $magick->getImageColorspace();
		if ($cs === \Gmagick::COLORSPACE_SRGB || $cs === \Gmagick::COLORSPACE_RGB)
			return $this->rgb;
		elseif ($cs === \Gmagick::COLORSPACE_CMYK)
			return new \Imagine\Image\Palette\CMYK();
		elseif ($cs === \Gmagick::COLORSPACE_GRAY)
			return new \Imagine\Image\Palette\Grayscale();

		throw new \Imagine\Exception\RuntimeException('Gmagick: Only RGB, CMYK and Grayscale colorspaces are curently supported');
	}

}
