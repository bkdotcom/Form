<?php

namespace bdk\Form\ControlDefinitions;

use bdk\Form\Control;

/**
 * Postal Code
 */
class Postalcode extends Control
{

    protected function getDefaultProps($type)
    {
        return array(
            'attribs' => array(
                'pattern'   => '(\d{5})([\. -]?\d{4})?',
                'title'     => 'Zip code (+4 optional)',
            ),
        );
    }
}
