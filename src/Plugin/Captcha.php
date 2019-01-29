<?php

namespace bdk\Form\Plugin;

use bdk\Form;
use bdk\Form\Control;
use bdk\Html;

/**
 * Captcha
 */
class Captcha
{
    // public $font = 'monofont.ttf';
    // var $font = 'bennyb.ttf';
    public $cfg = array(
        'code' => null,
        'dotDivisor' => 3,        // higher # less dots;
        'font' => 'monofont.ttf',
        'fontScale' => 0.75,      // % the image height
        'height' => 40,
        'length' => 5,
        'lineDivisor' => 150,
        'width' => 120,
    );
    public $form;

    /**
     * Constructor
     *
     * @param Form  $form form instance
     * @param array $cfg  config options
     */
    public function __construct(Form $form, $cfg = array())
    {
        $this->form = $form;
        $this->cfg = \array_merge($this->cfg, $cfg);
        if (!\file_exists($this->cfg['font']) && \file_exists(__DIR__.'/'.$this->cfg['font'])) {
            $this->cfg['font'] = __DIR__.'/'.$this->cfg['font'];
        }
    }

    /**
     * Generates image and stores in form's persist data
     *
     * @return string hash of image
     */
    public function generateImage()
    {
        $code = $this->getCode();
        $fontSize = $this->cfg['height'] * $this->cfg['fontScale'];
        $image = \imagecreate($this->cfg['width'], $this->cfg['height']);
        // set the colors
        \imagecolorallocate($image, 0, 0, 0); // background color
        $colorText = \imagecolorallocate($image, 200, 200, 200);
        $colorNoise = \imagecolorallocate($image, 150, 150, 150);
        $area = $this->cfg['width']*$this->cfg['height'];
        // generate random dots in background
        for ($i=0; $i < $area / $this->cfg['dotDivisor']; $i++) {
            \imagefilledellipse(
                $image,
                \mt_rand(0, $this->cfg['width']),   // center x
                \mt_rand(0, $this->cfg['height']),  // center y
                1,                                  // width
                1,                                  // height
                $colorNoise
            );
        }
        // generate random lines in background
        for ($i=0; $i < $area / $this->cfg['lineDivisor']; $i++) {
            \imageline(
                $image,
                \mt_rand(0, $this->cfg['width']),  // start x
                \mt_rand(0, $this->cfg['height']), // start y
                \mt_rand(0, $this->cfg['width']),  // end x
                \mt_rand(0, $this->cfg['height']), // end y
                $colorNoise
            );
        }
        // create textbox and add text
        $textbox = \imagettfbbox($fontSize, 0, $this->cfg['font'], $code);
        $baseX = ($this->cfg['width'] - $textbox[4])/2;
        $baseY = ($this->cfg['height'] - $textbox[5])/2;
        \imagettftext($image, $fontSize, 0, $baseX, $baseY, $colorText, $this->cfg['font'], $code);
        // output captcha image to browser
        \ob_start();
        \imagejpeg($image);
        $data = \ob_get_clean();
        $hash = $this->form->asset(array(
            'data' => $data,
            'headers' => array(
                'Last-Modified' => \date('r'),
                'Cache-Control' => 'no-cache, must-revalidate', // HTTP/1.1
                'Expires' =>  'Mon, 26 Jul 1997 05:00:00 GMT', // Date in the past
                'Content-Type' => 'image/jpeg',
                'Pragma' => 'no-cache',
            )
        ));
        \imagedestroy($image);
        return $hash;
    }

    /**
     * Generate & return <img> tag
     *
     * @return [type] [description]
     */
    public function output()
    {
        $url = $this->generateImage();
        return Html::buildTag(
            'img',
            array(
                'width' => $this->cfg['width'],
                'height' => $this->cfg['height'],
                'border' => 0,
                'src' => $url,
                'alt' => '',
            )
        );
    }

    /**
     * verify if passed string is the captcha code
     *
     * @param Control $control Control instance
     *
     * @return bean
     */
    public function validate(Control $control)
    {
        return $control->val() == $this->getCode();
    }

    /**
     * Generate code
     *
     * @return string
     */
    protected function getCode()
    {
        $code = $this->form->persist->get('global/captchaCode');
        if ($code && !$this->form->status['submitted']) {
            $code = null;
        }
        if (!$code) {
            $code = '';
            // list all possible characters, similar looking characters and vowels have been removed
            $possible = '23456789bcdfghjkmnpqrstvwxyz';
            for ($i=0; $i < $this->cfg['length']; $i++) {
                $code .= \substr($possible, \mt_rand(0, \strlen($possible)-1), 1);
            }
            $this->form->persist->set('global/captchaCode', $code);
        }
        return $code;
    }
}
