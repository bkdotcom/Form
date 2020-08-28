<?php

/**
 * @seehttps://getbootstrap.com/docs/4.3/components/forms/
 */

return array(
    'name' => 'bootstrap4',
    'defaultProps' => array(
        'default' => array(
            'attribs' => array(
                'class' => 'form-control',
            ),
            'attribsContainer' => array(
                'class' => 'form-group',
            ),
            'attribsControls' => array(
                'class' => 'controls',
            ),
            'attribsLabel' => array(
                'class' => 'control-label',
            ),
            'attribsHelpBlock' => array(
                'class' => 'form-text',
            ),
            'attribsInputGroup' => array(
                'class' => 'input-group',
            ),
        ),
        'button' => array(
            'attribs' => array(
                'class' => array('btn btn-light', 'replace'),
            ),
        ),
        'checkbox' => array(
            'attribs' => array(
                'class' => array('form-check-input', 'replace'),
            ),
            'attribsInputLabel' => array(
                'class' => 'form-check',
            ),
            'attribsLabel' => array(
                'class' => array('form-check-label', 'replace'),
            ),
            'inputLabelTemplate' => '<div {{attribsInputLabel}}>'
                . '{{input}}'
                . '<label {{attribsLabel}}>'
                    . '{{label}}'
                . '</label>'
                . '</div>' . "\n",
        ),
        'file' => array(
            'attribs' => array(
                'class' => array('form-control-file', 'replace'),
            ),
        ),
        'radio' => array(
            'attribs' => array(
                'class' => array('form-check-input', 'replace'),
            ),
            'attribsInputLabel' => array(
                'class' => 'form-check',
            ),
            'attribsLabel' => array(
                'class' => array('form-check-label', 'replace'),
            ),
            'inputLabelTemplate' => '<div {{attribsInputLabel}}>'
                . '{{input}}'
                . '<label {{attribsLabel}}>'
                    . '{{label}}'
                . '</label>'
                . '</div>' . "\n",
        ),
        'range' => array(
            'attribs' => array(
                'class' => array('form-control-range', 'replace'),
            ),
        ),
        'reset' => array(
            'attribs' => array(
                'class' => array('btn btn-light', 'replace'),
            ),
        ),
        'static' => array(
            'attribs' => array(
                'class' => array('form-control-plaintext', 'replace'),
            ),
        ),
        'submit' => array(
            'attribs' => array(
                'class' => array('btn btn-primary', 'replace'),
            ),
        ),
    ),
    'onBuildInputGroup' => function ($props) {
        $reButton = '#<(a|button|select)\b#i';
        if ($props['addonBefore']) {
            $addonClass = 'input-group-prepend';
            if (!\preg_match($reButton, $props['addonBefore'])) {
                $props['addonBefore'] = '<span class="input-group-text">' . $props['addonBefore'] . '</span>';
            }
            $props['addonBefore'] = '<span class="' . $addonClass . '">' . $props['addonBefore'] . '</span>';
        }
        if ($props['addonAfter']) {
            $addonClass = 'input-group-append';
            if (!\preg_match($reButton, $props['addonAfter'])) {
                $props['addonAfter'] = '<span class="input-group-text">' . $props['addonAfter'] . '</span>';
            }
            $props['addonAfter'] = '<span class="' . $addonClass . '">' . $props['addonAfter'] . '</span>';
        }
        return $props;
    },
);
