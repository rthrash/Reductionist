<?php
namespace Reductionist\Imagick;

use Imagine\Imagick\Imagine;
use Imagine\Imagick\Image;

use Imagine\Image\Box;
use Imagine\Image\Metadata\MetadataBag;
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


	public function open ($path) {
		return $this->Ropen($path, $this->nullBox);
	}


	public function Ropen($path, Box $size) {
		$handle = @fopen($path, 'r');

		if (false === $handle) {
			throw new \Imagine\Exception\InvalidArgumentException(sprintf('File %s doesn\'t exist', $path));
		}

		try {
			$magick = new \Imagick();
			if ($size !== $this->nullBox) {
				$magick->setOption('jpeg:size', "{$size->getWidth()}x{$size->getHeight()}");
			}
			$magick->readImageFile($handle);
			fclose($handle);
		}
		catch (\Exception $e) {
			fclose($handle);
			throw new \Imagine\Exception\RuntimeException("Imagick: Unable to open image $path. {$e->getMessage()}", $e->getCode(), $e);
		}

		return new Image($magick, $this->createPalette($magick), $this->emptyBag);
	}


	public function getImagine() {
		return $this->imagine;
	}


	private function createPalette(\Imagick $magick) {
		$cs = $magick->getImageColorspace();
		if ($cs === \Imagick::COLORSPACE_SRGB || $cs === \Imagick::COLORSPACE_RGB)
			return $this->rgb;
		elseif ($cs === \Imagick::COLORSPACE_CMYK)
			return new \Imagine\Image\Palette\CMYK();
		elseif ($cs === \Imagick::COLORSPACE_GRAY)
			return new \Imagine\Image\Palette\Grayscale();

		throw new \Imagine\Exception\RuntimeException('Imagick: Only RGB, CMYK and Grayscale colorspaces are curently supported');
	}

}
