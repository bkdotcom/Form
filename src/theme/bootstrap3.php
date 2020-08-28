<?php

return array(
    'name' => 'bootstrap3',
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
                'class' => 'help-block',
            ),
            'attribsInputGroup' => array(
            	'class' => 'input-group',
            ),
		),
        'button' => array(
            'attribs' => array(
                'class' => array('btn btn-default', 'replace'),
            ),
        ),
        'checkbox' => array(
            'attribsLabel' => array(
            	'class' => array(null, 'replace'),
            ),
            'attribsInputLabel' => array(
            	'class' => 'checkbox',
            ),
            'inputLabelTemplate' => '<div {{attribsInputLabel}}>'
                . '<label {{attribsLabel}}>'
	                . '{{input}}'
                    . '{{label}}'
                . '</label>'
                . '</div>' . "\n",
        ),
        'radio' => array(
            'attribsLabel' => array(
            	'class' => array(null, 'replace'),
            ),
            'attribsInputLabel' => array(
            	'class' => 'radio',
            ),
            'inputLabelTemplate' => '<div {{attribsInputLabel}}>'
                . '<label {{attribsLabel}}>'
	                . '{{input}}'
                    . '{{label}}'
                . '</label>'
                . '</div>' . "\n",
        ),
        'reset' => array(
            'attribs' => array(
                'class' => array('btn btn-default', 'replace'),
            ),
        ),
        'static' => array(
            'attribs' => array(
                'class' => array('form-control-static', 'replace'),
            ),
        ),
        'submit' => array(
            'attribs' => array(
                'class' => array('btn btn-default', 'replace'),
            ),
        ),
	),
	'onBuildInputGroup' => function ($props) {
        $reButton = '#<(a|button|select)\b#i';
        if ($props['addonBefore']) {
            $addonClass = \preg_match('#<(a|button|select)\b#i', $props['addonBefore'])
                ? 'input-group-btn'
                : 'input-group-addon';
            if (\preg_match($reButton, $props['addonBefore'])) {
                $props['addonBefore'] = '<span class="input-group-btn">' . $props['addonBefore'] . '</span>';
            }
            $props['addonBefore'] = '<span class="' . $addonClass . '">' . $props['addonBefore'] . '</span>';
        }
        if ($props['addonAfter']) {
            $addonClass = \preg_match('#<(a|button|select)\b#i', $props['addonAfter'])
                ? 'input-group-btn'
                : 'input-group-addon';
            if (\preg_match($reButton, $props['addonAfter'])) {
                $props['addonAfter'] = '<span class="input-group-btn">' . $props['addonAfter'] . '</span>';
            }
            $props['addonAfter'] = '<span class="' . $addonClass . '">' . $props['addonAfter'] . '</span>';
        }
        return $props;
	},
);
