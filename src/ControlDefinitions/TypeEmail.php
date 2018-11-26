<?php

namespace bdk\Form\ControlDefinitions;

use bdk\Form;
use bdk\Form\Control;
use bdk\Form\ControlBuilder;

/**
 * Email address
 */
class TypeEmail extends Control
{

    /**
     * {@inheritDoc}
     */
    public function __construct($props = array(), ControlBuilder $controlBuilder = null, Form $form = null)
    {
        $props = $this->mergeProps(array(
            array(
                'invalidReason' => 'This does not appear to be a valid email address',
            ),
            $props,
        ));
        parent::__construct($props, $controlBuilder, $form);
    }

    /**
     * Build field control
     *
     * @return string
     */
    public function doBuild()
    {
        $output = parent::build();
        if ($this->form && $this->form->status['submitted'] && !$this->props['isValid']) {
            // did not validate -> add hidden field
            $noticeName = $this->attribs['name'].'_notice';
            $output .= $this->form->controlBuilder->build(array(
                'type' => 'hidden',
                'name' => $noticeName,
                'value' => $this->val(),
            ));
        }
        return $output;
    }

    /**
     * Validate field
     *
     * @return boolean
     */
    public function doValidate()
    {
        $isValid = false;
        $value = $this->valRaw();
        $regex = '/^'
            .'[a-z0-9_]+([_\.-][a-z0-9]+)*'     // user
            .'@'
            .'([a-z0-9]+([\.-][a-z0-9]+)*)+'    // domain
            .'(\.[a-z]{2,})'                    // sld, tld
            .'$/i';
        if (\preg_match($regex, $value, $matches)) {
            $this->debug->log('properly formatted');
            $isValid    = true;
            $hostname   = \strtolower($matches[2].$matches[4]);
            $ipaddress  = \gethostbyname($hostname);        // A record
            if ($ipaddress == '92.242.140.2') {
                // bogus
                $ipaddress = $hostname;
            }
            $this->debug->log('hostname', $hostname);
            $this->debug->log('ipaddress', $ipaddress);
            if ($ipaddress != $hostname) {
                $this->debug->log('A record', $ipaddress);
            } elseif ($mxrecord = \getmxrr($hostname, $mxhosts)) {
                $this->debug->log('getmxrr('.$hostname.')', $mxrecord);
            } elseif (\strpos($_SERVER['SERVER_NAME'], $hostname)) {
                $this->debug->log('email domain matches server domain');
            } else {
                $this->debug->warn('unable to verify email');
                $noticeName = $this->attribs['name'].'_notice';
                if (!isset($this->form->currentValues[$noticeName]) || $this->form->currentValues[$noticeName] != $value) {
                    $this->debug->warn('flagging');
                    $this->flag('We were unable to confirm this address.<br />Please confirm before continuing.');
                } else {
                    $this->debug->info('have seen notice');
                }
            }
        }
        $this->debug->log('isValid', $isValid);
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
        return $field->valRaw();
    }
}
