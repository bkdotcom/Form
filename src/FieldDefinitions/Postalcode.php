<?php

namespace bdk\Form\FieldDefinitions;

use bdk\Form;
use bdk\Form\BuildControl;
use bdk\Form\Field;

/**
 * Postal Code
 */
class Postalcode extends Field
{

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), BuildControl $buildControl = null, Form $form = null)
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
        parent::__construct($props, $buildControl, $form);
    }
}
