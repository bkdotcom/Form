<?php

use bdk\Form;
use bdk\Form\Control;
use bdk\CssXpath\DOMTestCase;

/**
 * PHPUnit tests for BuildControl class
 */
class FormTest extends DOMTestCase
{

    private $callbackCounts = array(
        'pre' => 0,
        'post' => 0,
        'onComplete' => 0,
    );

    public function setUp()
    {
        Control::clearIdCounts();
        $this->setEnv();
    }

    protected function setEnv($post = array(), $server = array())
    {
        $_SERVER = array_merge($_SERVER, array(
            'REQUEST_URI' => '/path/myform?key=value',
            'REQUEST_METHOD' => !empty($post) ? 'POST' : 'GET',
            'QUERY_STRING' => '',
        ), $server);
        $_POST = $post;
        $_REQUEST = $post;
    }

    public function testFormConstruct()
    {
        $form = new Form(array(
            'controls' => array(
                'username' => array('required'=>true),
                'password' => array(
                    'attribs' => array(
                        'type'=>'password',
                        'pattern' => 'swordfish',   // must match swordfish
                    ),
                ),
            ),
            'pre' => function ($form) {
                $this->assertInstanceOf('\bdk\Form', $form);
                $this->callbackCounts['pre'] ++;
            },
            'post' => function ($form) {
                $this->assertInstanceOf('\bdk\Form', $form);
                $this->callbackCounts['post'] ++;
            },
            'onComplete' => function ($form) {
                $this->assertInstanceOf('\bdk\Form', $form);
                $this->callbackCounts['onComplete'] ++;
                return 'onComplete callback made this';
            },
        ));
        $form->process();
        $output = $form->output();
        $this->assertSelectCount('form', 1, $output);
        $this->assertSelectCount('input[type=hidden][name=_key_]', 1, $output);
        $this->assertSelectCount('.form-group .controls input[type=text][name=username][autofocus]', 1, $output);
        $this->assertSelectCount('.form-group .controls input[type=password][name=password]', 1, $output);
        $this->assertSelectCount('form button[type=submit]', 1, $output);
        $this->assertSelectEquals('form button[type=submit]', 'Submit', 1, $output);
        $this->assertSelectCount('script', 1, $output);
        // $this->assertSame('', $output);

        $this->assertInstanceOf('\bdk\Form\Control', $form->getControl('username'));
        $this->assertSame(null, $form->getValue('username'));
        // controls aren't invalid until the form is submitted
        $this->assertSame(array(), $form->invalidControls);
        $this->setEnv(array(
            '_key_' => $form->persist->key.'_0',
            'password' => 'wrong',
        ));
        $form->process();   // won't PRG because Phpunit has already output
        $this->assertTrue($form->submitted);
        $this->assertSame(array(
            $form->getControl('username'),    // required but empty
            $form->getControl('password'),    // not required, but invalid
        ), $form->invalidControls);
        /*
        */
        $this->setEnv(array(
            '_key_' => $form->persist->key.'_0',
            'username' => 'bkdotcom',
            'password' => 'swordfish',
        ));
        $form->process();   // won't PRG because Phpunit has already output
        $this->assertSame(array(), $form->invalidControls);
        $this->assertTrue($form->completed);
        $this->assertSame(1, $this->callbackCounts['onComplete']);
        $this->assertSame(3, $this->callbackCounts['pre']);
        $this->assertSame(2, $this->callbackCounts['post']);
        $output = $form->output();
        $this->assertSame('onComplete callback made this', $output);
    }
}
