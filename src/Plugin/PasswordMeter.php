<?php

namespace bdk\Form\Plugin;


/**
 * outputs password feedback html/css/javascript
 */
class PasswordMeter
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->debug = \bdk\Debug::getInstance();
    }

    /**
     * @param string $selector css selector
     *
     * @return string
     */
    function getPasswordMeter($selector)
    {
        $this->debug->group(__METHOD__);
        \add_head_tag('script','/common/js/pwd_meter/password-meter.js', array('defer'=>true));
        \add_head_tag('script','/common/js/pwd_meter/new_password_jquery.js', array('defer'=>true));
        $style = <<<EOD
    .passwordFeedback {
        /*
        margin-top:2em;
        */
    }
    #scorebarBorder {
        float:left;
        width: 100px;
        height: 20px;
        border: 1px #000 solid;
        margin-left: 1em;
        margin-right: 1em;
    }
    #score {
        position:absolute;
        margin-top:1px;
        width: 100px;
        z-index: 10;
        color: #000;
        font-weight: bold;
        line-height:normal;
        text-align: center;
    }
    #scorebar {
        background: url(/common/js/pwd_meter/images/bg_strength.jpg) repeat 0px 0px;
        position:absolute;
        width: 100px;
        height: 20px;
        z-index: 0;
    }
    #criteria {
        list-style-type:none;
        padding-left:0px;
        /*
        padding-top:10px;
        */
    }
    #criteria .checkbox {
        display: inline-block;
        width: 16px;
        height: 16px;
        min-height: inherit;
        margin: 0 .5em 0 0;
        padding: 0;
    }
    #criteria .checked .checkbox {
        background-image:url(/images/tiny/png-8/16-em-check.png);
    }
EOD;
        \append_content('style', $style);
        //
        $script = <<<EOD
    passwordmeter = new PasswordFeedback({
        selector : '{$selector}',
        criteria : ['alphaL','alphaU','exclude','length','numeric','special'],  // the checkmarks below
        init : function(pm) {
            pm.PasswordLength.minimum = 8;
            pm.PasswordLength.maximum = 32;
        },
        onchange : function(pm, pwd, matchOpacity) {
            console.log('pwdOnChange custom');
            var checkmarks = {
                    alphaL      : pm.LowercaseLetters.status,
                    alphaU      : pm.UppercaseLetters.status,
                    //exclude   : !pwd.match(/[&*( )< >^]/),
                    length      : pm.PasswordLength.status,
                    match       : matchOpacity,
                    numeric     : pm.Numerics.status,
                    special     : pm.Symbols.status
                },
                k = '',
                v = '',
                \$node = null;
            // console.log('cs',cs);
            // console.log('checkmarks',checkmarks);
            if (checkmarks.length) {
                // console.log('minimum met');
                $('.passwordFeedback .length .min').hide();
                $('.passwordFeedback .length .max').show();
                if ( pwd.length > pm.PasswordLength.maximum ) {
                    //console.log('too long');
                    checkmarks.length = false;
                }
            } else {
                $('.passwordFeedback .length .min').show();
                $('.passwordFeedback .length .max').hide();
            }
            for (k in checkmarks) {
                // console.log('pair.key',pair.key);
                v = checkmarks[k];
                \$node = $('#criteria .'+k);
                if ( v ) {
                    \$node.addClass('checked');
                    \$node.find('.checkbox').css({'opacity': v});
                } else {
                    \$node.removeClass('checked');
                }
            };
        } // end onchange
    });
EOD;
        append_content('script', $script);
        $html = <<<'EOD'
            <div class="passwordFeedback">
                <h3>Password Strength:</h3>
                <div id="scorebarBorder" class="reset-box-sizing">
                    <div id="score">0%</div>
                    <div id="scorebar">&nbsp;</div>
                </div>
                <span id="complexity">Too Short</span>
                <h3>Criteria:</h3>
                <ul id="criteria">
                    <li class="length"><span class="checkbox"></span><span class="min">Must be at least 8 characters long.</span><span class="max" style="display:none;">No more than 32 characters long.</span></li>
                    <li class="alphaL"><span class="checkbox"></span>Must contain at least one lowercase letter.</li>
                    <li class="alphaU"><span class="checkbox"></span>Must contain at least one uppercase letter.</li>
                    <li class="numeric"><span class="checkbox"></span>Must contain at least one number.</li>
                    <li class="special"><span class="checkbox"></span>Must contain at least one special character.</li>
                    <!--
                    <li class="exclude"><span class="checkbox"></span>Must NOT contain &amp;*()&lt;&gt;^ or space.</li>
                    -->
                    <li class="match"><span class="checkbox"></span>Passwords must match</li>
                </ul>
            </div>
EOD;
        $this->debug->groupEnd();
        return $html;
    }
}
