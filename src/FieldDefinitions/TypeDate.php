<?php

namespace bdk\Form\FieldDefinitions;

use bdk\Form;
use bdk\Form\BuildControl;
use bdk\Form\Field;

/**
 * Date
 */
class TypeDate extends Field
{

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), BuildControl $buildControl = null, Form $form = null)
    {
        $props = $this->mergeProps(array(
            array(
                'attribs' => array(
                    'class' => 'hide-spinbtns',
                    'placeholder' => 'yyyy-mm-dd',
                ),
            ),
            $props,
        ));
        parent::__construct($props, $buildControl, $form);
    }

    /**
     * [getDateArray description]
     *
     * @param string $value date value to parse
     *
     * @return array
     */
    private function getDateArray($value)
    {
        $dateArray = array();
        if (\preg_match('/^\d{8}$/', $value)) {
            // exactly 8 digits... assuming yyyymmdd
            $dateArray = array(
                'year'  => \substr($value, 0, 4),
                'mon'   => \substr($value, 4, 2),
                'mday'  => \substr($value, 6, 2),
            );
        } else {
            $slashString = \preg_replace('|[^/\d]|', '', $value);
            $parts = \explode('/', $slashString);
            if (\count($parts) == 3) {
                $dateArray = array(
                    'year'  => $parts[2],
                    'mon'   => $parts[0],
                    'mday'  => $parts[1],
                );
            } else {
                $dashString = \preg_replace('|[^-\d]|', '', $value);
                $parts = \explode('-', $dashString);
                if (\count($parts) == 3) {
                    $dateArray = array(
                        'year'  => $parts[0],
                        'mon'   => $parts[1],
                        'mday'  => $parts[2],
                    );
                }
            }
            // call mktime then getdate to get 4-digit-year
            if (!empty($dateArray)) {
                $dateTs = \mktime(0, 0, 0, (int) $dateArray['mon'], (int) $dateArray['mday'], (int) $dateArray['year']);
                $mktimeDateArray = \getdate($dateTs);
                $dateArray['year'] = $mktimeDateArray['year'];
            }
        }
        return $dateArray;
    }

    /**
     * Validate field
     *
     * @return boolean
     */
    public function doValidate()
    {
        $value = $this->valRaw();
        $dateArray = $this->getDateArray($value);
        $isValid = !empty($dateArray) && \checkdate((int) $dateArray['mon'], (int) $dateArray['mday'], (int) $dateArray['year']);
        return $isValid;
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
        if ($this->validate($field)) {
            $value = $field->valRaw();
            $dateArray = $this->getDateArray($value);
            return \sprintf('%04d-%02d-%02d', $dateArray['year'], $dateArray['mon'], $dateArray['mday']);
        }
        return null;
    }
}
