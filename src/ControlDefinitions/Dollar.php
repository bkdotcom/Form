<?php

namespace bdk\Form\ControlDefinitions;

use bdk\Form;
use bdk\Form\Control;
use bdk\Form\ControlBuilder;

/**
 * Dollar Amount
 */
class Dollar extends Control
{

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), ControlBuilder $controlBuilder = null, Form $form = null)
    {
        $props = $this->mergeProps(array(
            array(
                'attribs' => array(
                    'pattern'       => '(-?\$?|\$-)(?=[\d.])\d{0,3}(,?\d{3})*(\.\d{1,2})?$',        // must be at least one digit
                    'placeholder'   => 'xxxx.xx',
                    'size'          => 12,
                    'title'         => 'xxxx.xx',
                ),
                'addonBefore'   => '<i class="glyphicon glyphicon-usd"></i>',
                'invalidReason' => 'Should be in the form $xxxx.xx',
            ),
            $props,
        ));
        parent::__construct($props, $controlBuilder, $form);
    }

    /**
     * Get formated value
     *
     * @param Control $control instance
     *
     * @return string
     */
    public function getValFormatted(Control $control)
    {
        $val = $control->valRaw();
        $val = \preg_replace('/[^\d.-]/', '', $val);
        $val = \strlen($val)
            ? \sprintf('%.2f', $val)
            : null;
        return $val === null
            ? null
            : '$'.\number_format($val, 2);
    }
}
