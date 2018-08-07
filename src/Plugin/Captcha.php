<?php

namespace bdk\Form\Plugin;

use bdk\Session;

/**
 * Captcha
 */
class Captcha
{
	public $font = 'monofont.ttf';
	// var $font = 'bennyb.ttf';
	public $font_scale = 0.75;		// default 0.75;	% the image height
	public $dot_divisor = 3;		// default 3;		higher # less dots;
	public $line_divisor = 150;		// default 150;
	public $length = 5;

	/**
	 * Constructor
	 *
	 * @param array $cfg config options
	 */
	public function __construct($cfg = array())
	{
		Session::start();
		foreach ($cfg as $k => $v) {
			$this->cfg[$k] = $v;
		}
		if (\file_exists(__DIR__.'/'.$this->font)) {
			$this->font = __DIR__.'/'.$this->font;
		}
	}

	/**
	 * Generate code
	 *
	 * @param integer $length number of characters in generated code
	 *
	 * @return string
	 */
	public function generateCode($length)
	{
		if (isset($_SESSION['captcha_code'])) {
			$code = $_SESSION['captcha_code'];
		} else {
			// list all possible characters, similar looking characters and vowels have been removed
			$possible = '23456789bcdfghjkmnpqrstvwxyz';
			$code = '';
			for ($i=0; $i < $length; $i++) {
				$code .= \substr($possible, \mt_rand(0, \strlen($possible)-1), 1);
			}
			$_SESSION['captcha_code'] = $code;
		}
		return $code;
	}

	/**
	 * Outputs image and appropriate headers
	 *
	 * @param array $params width, height, & length
	 *
	 * @return void
	 */
	public function outputImage($params = array())
	{
		$param_defaults = array(
			'width' => 120,
			'height' => 40,
			// 'length' => 6
		);
		$params = \array_merge($param_defaults, $params);
		$code = $this->generateCode($this->length);
		$font_size = $params['height'] * $this->font_scale;
		$image = @\imagecreate($params['width'], $params['height']) or die('Cannot initialize new GD image stream');
		// set the colors
		$color_bkg = \imagecolorallocate($image, 0, 0, 0);
		// $color_text = imagecolorallocate($image, 20, 40, 100);
		$color_text = \imagecolorallocate($image, 200, 200, 200);
		$color_noise = \imagecolorallocate($image, 150, 150, 150);
		// generate random dots in background
		for ($i=0; $i<($params['width']*$params['height'])/$this->dot_divisor; $i++) {
			\imagefilledellipse($image, \mt_rand(0, $params['width']), \mt_rand(0, $params['height']), 1, 1, $color_noise);
		}
		// generate random lines in background
		for ($i=0; $i<($params['width']*$params['height'])/$this->line_divisor; $i++) {
			\imageline($image, \mt_rand(0, $params['width']), \mt_rand(0, $params['height']), \mt_rand(0, $params['width']), \mt_rand(0, $params['height']), $color_noise);
		}
		// create textbox and add text
		$textbox = \imagettfbbox($font_size, 0, $this->font, $code); // or die('Error in imagettfbbox function');
		$x = ($params['width'] - $textbox[4])/2;
		$y = ($params['height'] - $textbox[5])/2;
		\imagettftext($image, $font_size, 0, $x, $y, $color_text, $this->font, $code); // or die('Error in imagettftext function');
		// output captcha image to browser
		\header('Last-Modified: '.\date('r'));
		\header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		\header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		\header('Content-Type: image/jpeg');
		\header('Pragma: no-cache');
		\imagejpeg($image);
		\imagedestroy($image);
	}

	/**
	 * verify if passed string is the captcha code
	 *
	 * @param string $str test string
	 *
	 * @return bean
	 */
	public function verify($str)
	{
		$return = false;
		if (isset($_SESSION['captcha_code'])) {
			// debug('captcha_code', $_SESSION['captcha_code']);
			if ($str == $_SESSION['captcha_code']) {
				$return = true;
			}
		}
		return $return;
	}
}
