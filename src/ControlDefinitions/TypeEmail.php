<?php

namespace bdk\Form\ControlDefinitions;

use bdk\Form\Control;

/**
 * Email address
 */
class TypeEmail extends Control
{

    /**
     * Build control control
     *
     * @return string
     */
    public function doBuild()
    {
        $output = parent::build();
        if (!$this->props['isValid']) {
            // did not validate -> add hidden control
            $hiddenControl = $this->controlFactory->build(array(
                'type' => 'hidden',
                'name' => $this->attribs['name'].'_notice',
                'value' => $this->val(),
            ))->build();
            $output = \preg_replace('#(<input[^>]+>)#', '$1'."\n".$hiddenControl, $output);
        }
        return $output;
    }

    /**
     * Validate control
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
     * @param Control $control instance
     *
     * @return string
     */
    public function getValFormatted(Control $control)
    {
        return $control->valRaw();
    }

    protected function getDefaultProps($type)
    {
        return array(
            'invalidReason' => 'This does not appear to be a valid email address',
        );
    }
}
