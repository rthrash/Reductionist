<?php
namespace Reductionist\Gd;

use Imagine\Gd\Imagine;
use Imagine\Gd\Image;

use Imagine\Image\Metadata\MetadataBag;
use Imagine\Image\Palette\RGB;


class RImagine {
	protected $imagine;
	protected $emptyBag;
	protected $rgb;


	public function __construct() {
		$this->imagine = new Imagine();
		$this->emptyBag = new MetadataBag();
		$this->rgb = new RGB();
	}


	public function __call($method, $args) {
		return call_user_func_array(array($this->imagine, $method), $args);
	}


	public function open ($path) {
		$data = @file_get_contents($path);

		if (false === $data) {
			throw new \Imagine\Exception\InvalidArgumentException(sprintf('File %s doesn\'t exist', $path));
		}

		$resource = @imagecreatefromstring($data);

		if (!is_resource($resource)) {
			throw new \Imagine\Exception\InvalidArgumentException(sprintf('GD: Unable to open image %s', $path));
		}

		return $this->wrap($resource);
	}


	private function wrap($resource) {
		if (!imageistruecolor($resource)) {
			list($width, $height) = array(imagesx($resource), imagesy($resource));

			// create transparent truecolor canvas
			$truecolor   = imagecreatetruecolor($width, $height);
			$transparent = imagecolorallocatealpha($truecolor, 255, 255, 255, 127);

			imagefill($truecolor, 0, 0, $transparent);
			imagecolortransparent($truecolor, $transparent);

			imagecopymerge($truecolor, $resource, 0, 0, 0, 0, $width, $height, 100);

			imagedestroy($resource);
			$resource = $truecolor;
		}

		if (!imagealphablending($resource, false) || !imagesavealpha($resource, true)) {
			throw new \Imagine\Exception\RuntimeException('GD: Could not set alphablending or savealpha');
		}

		if (function_exists('imageantialias')) {
			imageantialias($resource, true);
		}

		return new Image($resource, $this->rgb, $this->emptyBag);
	}

}
