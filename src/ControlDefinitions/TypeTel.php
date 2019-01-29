<?php

namespace bdk\Form\ControlDefinitions;

use bdk\Form\Control;

/**
 * Telephone Number
 */
class TypeTel extends Control
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
        $num = \preg_replace('/\D/', '', $val);
        $parts = \sscanf($num, '%3s%3s%4s');
        return $parts
            ? \vsprintf('(%s) %s-%s', $parts)
            : '';
    }

    protected function getDefaultProps($type)
    {
        return array(
            'attribs' => array(
                'pattern'       => '\(?[2-9]\d{2}[)-.]?[\s]?\d{3}[ -.]?\d{4}',
                'placeholder'   => '(nnn) nnn-nnnn',
                'title'         => 'Phone: (nnn) nnn-nnnn',
            ),
            'invalidReason' => 'Must be formatted (nnn) nnn-nnnn',
        );
    }
}
