<?php

namespace bdk\Form\ControlDefinitions;

use bdk\Form\Control;

/**
 * Dollar Amount
 */
class Dollar extends Control
{

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

    protected function getDefaultProps($type)
    {
        return array(
            'attribs' => array(
                'pattern'       => '(-?\$?|\$-)(?=[\d.])\d{0,3}(,?\d{3})*(\.\d{1,2})?$',        // must be at least one digit
                'placeholder'   => 'xxxx.xx',
                'size'          => 12,
                'title'         => 'xxxx.xx',
            ),
            'addonBefore'   => '<i class="glyphicon glyphicon-usd"></i>',
            'invalidReason' => 'Should be in the form $xxxx.xx',
        );
    }
}
