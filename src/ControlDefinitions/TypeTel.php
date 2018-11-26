<?php

namespace bdk\Form\ControlDefinitions;

use bdk\Form;
use bdk\Form\Control;
use bdk\Form\ControlBuilder;

/**
 * Telephone Number
 */
class TypeTel extends Control
{

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), ControlBuilder $controlBuilder = null, Form $form = null)
    {
        $props = $this->mergeProps(array(
            array(
                'attribs' => array(
                    'pattern'       => '\(?[2-9]\d{2}[)-.]?[\s]?\d{3}[ -.]?\d{4}',
                    'placeholder'   => '(nnn) nnn-nnnn',
                    'title'         => 'Phone: (nnn) nnn-nnnn',
                ),
                'invalidReason' => 'Must be formatted (nnn) nnn-nnnn',
            ),
            $props,
        ));
        parent::__construct($props, $controlBuilder, $form);
    }

    /**
     * Get formated value
     *
     * @param object $field instance
     *
     * @return string
     */
    public function getValFormatted($field)
    {
        $val = $field->valRaw();
        $num = \preg_replace('/\D/', '', $val);
        $parts = \sscanf($num, '%3s%3s%4s');
        return $parts
            ? \vsprintf('(%s) %s-%s', $parts)
            : '';
    }
}
