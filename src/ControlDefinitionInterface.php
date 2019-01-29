<?php

namespace bdk\Form;

use bdk\Form\Control;

interface ControlDefinitionInterface
{

    /**
     * Returns default properties & attributes
     *
     * @return array
     */
    public function getDefaultProps();

    /**
     * Get formated value
     *
     * @param Control $control control instance
     *
     * @return string
     */
    public function getValFormatted(Control $control);

    /**
     * Validate control
     *
     * @param Control $control control instance
     *
     * @return boolean
     */
    public function validate(Control $control);
}
