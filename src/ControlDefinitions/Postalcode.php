<?php

namespace bdk\Form\ControlDefinitions;

use bdk\Form;
use bdk\Form\Control;
use bdk\Form\ControlBuilder;

/**
 * Postal Code
 */
class Postalcode extends Control
{

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), ControlBuilder $controlBuilder = null, Form $form = null)
    {
        $props = $this->mergeProps(array(
            array(
                'attribs' => array(
                    'pattern'   => '(\d{5})([\. -]?\d{4})?',
                    'title'     => 'Zip code (+4 optional)',
                ),
            ),
            $props,
        ));
        parent::__construct($props, $controlBuilder, $form);
    }
}
