<?php

use bdk\Form\ControlBuilder;
use bdk\Form\ControlFactory;

class DataProvider
{

    public static function buildProvider()
    {
        $controlFactory = new ControlFactory(
            null,
            array(
                'attribs' => array(
                    'class' => 'input-sm',
                ),
                'idPrefix' => 'unittest'
            )
        );
        return array(
            // date
            array(
                $controlFactory,
                array(
                    'type' => 'date',
                    'name' => 'dob',
                    'label' => 'birthday',
                    'helpBlock' => 'The date of your birth',
                    'addonAfter' => '<i class="glyphicon glyphicon-calendar"></i>'
                ),
                '<div class="form-group" id="unittest_dob_container">
                <label class="control-label" for="unittest_dob">birthday</label>
                <div class="controls">
                <div class="input-group">
                <input aria-describedby="unittest_dob_help_block" class="form-control hide-spinbtns input-sm" id="unittest_dob" name="dob" placeholder="yyyy-mm-dd" type="date" />
                <span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
                </div>
                <span class="help-block" id="unittest_dob_help_block">The date of your birth</span>
                </div>
                </div>',
                '<input class="form-control hide-spinbtns input-sm" id="unittest_dob_2" name="dob" placeholder="yyyy-mm-dd" type="date" />',
            ),
            // datetime-local
            array(
                $controlFactory,
                array(
                    'type' => 'datetime-local',
                    'name' => 'starttime',
                    'label' => 'When',
                ),
                '<div class="form-group" id="unittest_starttime_container">
                <label class="control-label" for="unittest_starttime">When</label>
                <div class="controls">
                <input class="form-control hide-spinbtns input-sm" id="unittest_starttime" name="starttime" placeholder="yyyy-mm-ddThh:mm:ss" type="datetime-local" />
                </div>
                </div>',
                '<input class="form-control hide-spinbtns input-sm" id="unittest_starttime_2" name="starttime" placeholder="yyyy-mm-ddThh:mm:ss" type="datetime-local" />',
            ),
            // email
            array(
                $controlFactory,
                array(
                    'type' => 'email',
                    'name' => 'email',
                    'label' => 'email',
                    'required' => true,
                    'addonAfter' => '@',
                ),
                '<div class="form-group required" id="unittest_email_container">
                <label class="control-label" for="unittest_email">email</label>
                <div class="controls">
                <div class="input-group">
                <input class="form-control input-sm" id="unittest_email" name="email" required="required" type="email" x-moz-errormessage="This does not appear to be a valid email address" />
                <span class="input-group-addon">@</span>
                </div>
                </div>
                </div>',
                '<input class="form-control input-sm" id="unittest_email_2" name="email" required="required" type="email" x-moz-errormessage="This does not appear to be a valid email address" />',
            ),

            // email
            array(
                $controlFactory,
                array(
                    'type' => 'email',
                    'name' => 'email_invalid',
                    'label' => 'email (invalid)',
                    'required' => true,
                    'addonAfter' => '@',
                    'isValid' => false,
                ),
                '<div class="form-group has-error required" id="unittest_email_invalid_container">
                <label class="control-label" for="unittest_email_invalid">email (invalid)</label>
                <div class="controls">
                <div class="input-group">
                <input aria-describedby="unittest_email_invalid_help_block" class="form-control input-sm" id="unittest_email_invalid" name="email_invalid" required="required" type="email" x-moz-errormessage="This does not appear to be a valid email address" />
                <input id="unittest_email_invalid_notice" name="email_invalid_notice" type="hidden" />
                <span class="input-group-addon">@</span>
                </div>
                <span class="help-block" id="unittest_email_invalid_help_block">This does not appear to be a valid email address</span>
                </div>
                </div>',
                '<input class="form-control input-sm" id="unittest_email_invalid_2" name="email_invalid" required="required" type="email" x-moz-errormessage="This does not appear to be a valid email address" />
                <input id="unittest_email_invalid_notice_2" name="email_invalid_notice" type="hidden" />',
            ),

            // file
            array(
                $controlFactory,
                array(
                    'type' => 'file',
                    'name' => 'imageupload',
                    'label' => 'profile image',
                ),
                '<div class="form-group" id="unittest_imageupload_container">
                <label class="control-label" for="unittest_imageupload">profile image</label>
                <div class="controls">
                <input id="unittest_imageupload" name="imageupload" type="file" />
                </div>
                </div>',
                '<input id="unittest_imageupload_2" name="imageupload" type="file" />',
            ),
            // hidden
            array(
                $controlFactory,
                array(
                    'type' => 'hidden',
                    'name' => 'secret',
                    'value' => 'sesame',
                ),
                '<input id="unittest_secret" name="secret" type="hidden" value="sesame" />',
                '<input id="unittest_secret_2" name="secret" type="hidden" value="sesame" />',
            ),
            // html
            array(
                $controlFactory,
                array(
                    'type' => 'html',
                    'value' => '<div class="col-sm-6">HTML</div>',
                ),
                '<div class="col-sm-6">HTML</div>',
                '<div class="col-sm-6">HTML</div>',
            ),
            // number
            array(
                $controlFactory,
                array(
                    'type' => 'number',
                    'name' => 'favoriteNumber',
                    'label' => 'Number',
                    'attribs' => array(
                        'min' => 1,
                        'max' => 10,
                    ),
                ),
                '<div class="form-group" id="unittest_favoriteNumber_container">
                <label class="control-label" for="unittest_favoriteNumber">Number</label>
                <div class="controls">
                <input class="form-control input-sm" id="unittest_favoriteNumber" max="10" min="1" name="favoriteNumber" step="any" type="number" />
                </div>
                </div>',
                '<input class="form-control input-sm" id="unittest_favoriteNumber_2" max="10" min="1" name="favoriteNumber" step="any" type="number" />',
            ),
            // password
            array(
                $controlFactory,
                array(
                    'type' => 'password',
                    'name' => 'password',
                    'label' => 'Password',
                    'attribs' => array(
                        'data-lpignore' => true,    // no LastPass icon
                    ),
                ),
                '<div class="form-group" id="unittest_password_container">
                <label class="control-label" for="unittest_password">Password</label>
                <div class="controls">
                <input autocapitalize="none" autocomplete="off" autocorrect="off" class="form-control input-sm" data-lpignore="true" id="unittest_password" name="password" type="password" />
                </div>
                </div>',
                '<input autocapitalize="none" autocomplete="off" autocorrect="off" class="form-control input-sm" data-lpignore="true" id="unittest_password_2" name="password" type="password" />',
            ),
            // range
            array(
                $controlFactory,
                array(
                    'type' => 'range',
                    'name' => 'range',
                    'label' => 'home on the range',
                ),
                '<div class="form-group" id="unittest_range_container">
                <label class="control-label" for="unittest_range">home on the range</label>
                <div class="controls">
                <input id="unittest_range" max="100" min="0" name="range" step="1" type="range" />
                </div>
                </div>',
                '<input id="unittest_range_2" max="100" min="0" name="range" step="1" type="range" />',
            ),
            array(
                // search
                $controlFactory,
                array(
                    'label' => 'Search',
                    'type' => 'search',
                    // 'addonAfter' => '🔍',
                    'addonAfter' => '<i class="glyphicon glyphicon-search"></i>'
                ),
                '<div class="form-group">
                <label class="control-label">Search</label>
                <div class="controls">
                <div class="input-group">
                <input class="form-control input-sm" placeholder="search" type="search" />
                <span class="input-group-addon"><i class="glyphicon glyphicon-search"></i></span>
                </div>
                </div>
                </div>',
                '<input class="form-control input-sm" placeholder="search" type="search" />',
            ),
            array(
                // select (option values provided)
                $controlFactory,
                array(
                    'type' => 'select',
                    'name' => 'things',
                    'options' => array('Hammer', 'Banana', 'Pillow', 'Desk', 'Stick'),
                    'label' => 'Select a thing (not required)'
                ),
                '<div class="form-group" id="unittest_things_container">
                <label class="control-label" for="unittest_things">Select a thing (not required)</label>
                <div class="controls">
                <select class="form-control input-sm" id="unittest_things" name="things">
                <option selected="selected" value="">Select</option>
                <option value="Hammer">Hammer</option>
                <option value="Banana">Banana</option>
                <option value="Pillow">Pillow</option>
                <option value="Desk">Desk</option>
                <option value="Stick">Stick</option>
                </select>
                </div>
                </div>',
                '<select class="form-control input-sm" id="unittest_things_2" name="things">
                <option selected="selected" value="">Select</option>
                <option value="Hammer">Hammer</option>
                <option value="Banana">Banana</option>
                <option value="Pillow">Pillow</option>
                <option value="Desk">Desk</option>
                <option value="Stick">Stick</option>
                </select>',
            ),
            array(
                // select (with groups)
                $controlFactory,
                array(
                    'name' => 's2',
                    'label' => 'I have opt groups!',
                    'type' => 'select',
                    'required' => true,
                    'options' => array(
                        'not in group',
                        array('optgroup'=>true, 'label'=>'Group 1'),
                        'Option 1.1',
                        array('optgroup'=>true, 'label'=>'Group 2'),
                        'Option 2.1',
                        'Option 2.2',
                        array('optgroup'=>true, 'label'=>'Group 3 (disabled)', 'disabled' => true),
                        'Option 3.1',
                        'Option 3.2',
                        'Option 3.3',
                        array('optgroup'=>false),
                        'outside of group',
                    ),
                ),
                '<div class="form-group required" id="unittest_s2_container">
                <label class="control-label" for="unittest_s2">I have opt groups!</label>
                <div class="controls">
                    <select class="form-control input-sm" id="unittest_s2" name="s2" required="required">
                        <option disabled="disabled" selected="selected" value="">Select</option>
                        <option value="not in group">not in group</option>
                        <optgroup label="Group 1">
                            <option value="Option 1.1">Option 1.1</option>
                        </optgroup>
                        <optgroup label="Group 2">
                            <option value="Option 2.1">Option 2.1</option>
                            <option value="Option 2.2">Option 2.2</option>
                        </optgroup>
                        <optgroup disabled="disabled" label="Group 3 (disabled)">
                            <option value="Option 3.1">Option 3.1</option>
                            <option value="Option 3.2">Option 3.2</option>
                            <option value="Option 3.3">Option 3.3</option>
                        </optgroup>
                        <option value="outside of group">outside of group</option>
                    </select>
                </div>
                </div>',
                '<select class="form-control input-sm" id="unittest_s2_2" name="s2" required="required">
                    <option disabled="disabled" selected="selected" value="">Select</option>
                    <option value="not in group">not in group</option>
                    <optgroup label="Group 1">
                        <option value="Option 1.1">Option 1.1</option>
                    </optgroup>
                    <optgroup label="Group 2">
                        <option value="Option 2.1">Option 2.1</option>
                        <option value="Option 2.2">Option 2.2</option>
                    </optgroup>
                    <optgroup disabled="disabled" label="Group 3 (disabled)">
                        <option value="Option 3.1">Option 3.1</option>
                        <option value="Option 3.2">Option 3.2</option>
                        <option value="Option 3.3">Option 3.3</option>
                    </optgroup>
                    <option value="outside of group">outside of group</option>
                </select>',
            ),
            // select (multi)
            array(
                $controlFactory,
                array(
                    'name' => 's3',
                    'label' => 'Select multiple things!',
                    'type' => 'select',
                    'required' => true,
                    'multiple' => true,
                    'options' => array('gps', 'smartphone', 'knick knacks'),
                ),
                '<div class="form-group required" id="unittest_s3_container">
                <label class="control-label" for="unittest_s3">Select multiple things!</label>
                <div class="controls">
                <select class="form-control input-sm" id="unittest_s3" multiple="multiple" name="s3[]" required="required">
                <option value="gps">gps</option>
                <option value="smartphone">smartphone</option>
                <option value="knick knacks">knick knacks</option>
                </select>
                </div>
                </div>',
                '<select class="form-control input-sm" id="unittest_s3_2" multiple="multiple" name="s3[]" required="required">
                <option value="gps">gps</option>
                <option value="smartphone">smartphone</option>
                <option value="knick knacks">knick knacks</option>
                </select>',
            ),

            // static
            array(
                $controlFactory,
                array(
                    'type' => 'static',
                    'name' => 'static_test',
                    'label' => 'Cereal',
                    'value' => 'Cinnamon Toast Crunch',
                ),
                '<div class="form-group" id="unittest_static_test_container">
                <label class="control-label" for="unittest_static_test">Cereal</label>
                <div class="controls">
                <div class="form-control-static" id="unittest_static_test">Cinnamon Toast Crunch</div>
                </div>
                </div>',
                '<div class="form-control-static" id="unittest_static_test_2">Cinnamon Toast Crunch</div>',
            ),
            // tel
            array(
                $controlFactory,
                array(
                    'type' => 'tel',
                    'name' => 'homePhone',
                    'label' => 'Home Phone',
                    'addonAfter' => '<i class="glyphicon glyphicon-earphone"></i>'
                ),
                '<div class="form-group" id="unittest_homePhone_container">
                <label class="control-label" for="unittest_homePhone">Home Phone</label>
                <div class="controls">
                <div class="input-group">
                <input class="form-control input-sm" id="unittest_homePhone" name="homePhone" pattern="\(?[2-9]\d{2}[)-.]?[\s]?\d{3}[ -.]?\d{4}" placeholder="(nnn) nnn-nnnn" title="Phone: (nnn) nnn-nnnn" type="tel" x-moz-errormessage="Must be formatted (nnn) nnn-nnnn" />
                <span class="input-group-addon"><i class="glyphicon glyphicon-earphone"></i></span>
                </div>
                </div>
                </div>',
                '<input class="form-control input-sm" id="unittest_homePhone_2" name="homePhone" pattern="\(?[2-9]\d{2}[)-.]?[\s]?\d{3}[ -.]?\d{4}" placeholder="(nnn) nnn-nnnn" title="Phone: (nnn) nnn-nnnn" type="tel" x-moz-errormessage="Must be formatted (nnn) nnn-nnnn" />',
            ),

            // text (default)
            array(
                $controlFactory,
                array(
                    'name' => 'name',
                    'label' => 'Super Default',
                ),
                '<div class="form-group" id="unittest_name_container">
                <label class="control-label" for="unittest_name">Super Default</label>
                <div class="controls">
                <input class="form-control input-sm" id="unittest_name" name="name" type="text" />
                </div>
                </div>',
                '<input class="form-control input-sm" id="unittest_name_2" name="name" type="text" />',
            ),

            array(
                // textarea
                $controlFactory,
                array(
                    'type' => 'textarea',
                    'name' => 'essay',
                    'label' => 'say some things',
                ),
                '<div class="form-group" id="unittest_essay_container">
                <label class="control-label" for="unittest_essay">say some things</label>
                <div class="controls">
                <textarea class="form-control input-sm" id="unittest_essay" name="essay" rows="4"></textarea>
                </div>
                </div>',
                '<textarea class="form-control input-sm" id="unittest_essay_2" name="essay" rows="4"></textarea>',
            ),
            array(
                // url
                $controlFactory,
                array(
                    'type' => 'url',
                    'name' => 'homepage',
                    'label' => 'Home Page',
                    'addonAfter' => '<i class="glyphicon glyphicon-globe"></i>',
                ),
                '<div class="form-group" id="unittest_homepage_container">
                <label class="control-label" for="unittest_homepage">Home Page</label>
                <div class="controls">
                <div class="input-group">
                <input class="form-control input-sm" id="unittest_homepage" name="homepage" pattern="https?://([-\w\.]+)+(:\d+)?(/([-\w/\.]*(\?\S+)?)?)?" placeholder="http://" type="url" />
                <span class="input-group-addon"><i class="glyphicon glyphicon-globe"></i></span>
                </div>
                </div>
                </div>',
                '<input class="form-control input-sm" id="unittest_homepage_2" name="homepage" pattern="https?://([-\w\.]+)+(:\d+)?(/([-\w/\.]*(\?\S+)?)?)?" placeholder="http://" type="url" />',
            ),

            /*
                Definitions
            */
            array(
                // creditcard
                $controlFactory,
                array(
                    'name' => 'ccnum',
                    'label' => 'Credit Card',
                    'definition' => 'creditcard',
                    'addonAfter' => '<i class="glyphicon glyphicon-credit-card"></i>'
                ),
                '<div class="form-group" id="unittest_ccnum_container">
                <label class="control-label" for="unittest_ccnum">Credit Card</label>
                <div class="controls">
                <div class="input-group">
                <input autocomplete="off" class="form-control input-sm" data-lpignore="true" id="unittest_ccnum" maxlength="19" name="ccnum" pattern="((4\d{3}|5[1-5]\d{2}|6011)([- ]?\d{4}){3}|3[47]\d{2}[- ]?\d{6}[- ]?\d{5})" placeholder="nnnn-nnnn-nnnn-nnnn" size="18" title="nnnn-nnnn-nnnn-nnnn" type="text" x-moz-errormessage="Must be a valid credit card #" />
                <span class="input-group-addon"><i class="glyphicon glyphicon-credit-card"></i></span>
                </div>
                </div>
                </div>',
                '<input autocomplete="off" class="form-control input-sm" data-lpignore="true" id="unittest_ccnum_2" maxlength="19" name="ccnum" pattern="((4\d{3}|5[1-5]\d{2}|6011)([- ]?\d{4}){3}|3[47]\d{2}[- ]?\d{6}[- ]?\d{5})" placeholder="nnnn-nnnn-nnnn-nnnn" size="18" title="nnnn-nnnn-nnnn-nnnn" type="text" x-moz-errormessage="Must be a valid credit card #" />',
            ),
            // dollar
            array(
                $controlFactory,
                array(
                    'name' => 'donation',
                    'label' => 'Donation',
                    'definition' => 'dollar',
                ),
                '<div class="form-group" id="unittest_donation_container">
                <label class="control-label" for="unittest_donation">Donation</label>
                <div class="controls">
                <div class="input-group">
                <span class="input-group-addon"><i class="glyphicon glyphicon-usd"></i></span>
                <input class="form-control input-sm" id="unittest_donation" name="donation" pattern="(-?\$?|\$-)(?=[\d.])\d{0,3}(,?\d{3})*(\.\d{1,2})?$" placeholder="xxxx.xx" size="12" title="xxxx.xx" type="text" x-moz-errormessage="Should be in the form $xxxx.xx" />
                </div>
                </div>
                </div>',
                '<input class="form-control input-sm" id="unittest_donation_2" name="donation" pattern="(-?\$?|\$-)(?=[\d.])\d{0,3}(,?\d{3})*(\.\d{1,2})?$" placeholder="xxxx.xx" size="12" title="xxxx.xx" type="text" x-moz-errormessage="Should be in the form $xxxx.xx" />',
            ),
            // postalcode
            array(
                $controlFactory,
                array(
                    'name' => 'postalCode',
                    'label' => 'Zipcode',
                    'definition' => 'postalcode',
                ),
                '<div class="form-group" id="unittest_postalCode_container">
                <label class="control-label" for="unittest_postalCode">Zipcode</label>
                <div class="controls">
                <input class="form-control input-sm" id="unittest_postalCode" name="postalCode" pattern="(\d{5})([\. -]?\d{4})?" title="Zip code (+4 optional)" type="text" />
                </div>
                </div>',
                '<input class="form-control input-sm" id="unittest_postalCode_2" name="postalCode" pattern="(\d{5})([\. -]?\d{4})?" title="Zip code (+4 optional)" type="text" />',
            ),
            // ssn
            array(
                $controlFactory,
                array(
                    'name' => 'ssn',
                    'label' => 'SSN',
                    'definition' => 'ssn'
                ),
                '<div class="form-group" id="unittest_ssn_container">
                <label class="control-label" for="unittest_ssn">SSN</label>
                <div class="controls">
                <input autocomplete="off" class="form-control input-sm" id="unittest_ssn" name="ssn" pattern="\d{3}[\. -]?\d{2}[\. -]?\d{4}" placeholder="nnn-nn-nnnn" size="11" title="SSN: nnn-nnnn" type="text" x-moz-errormessage="Must be formatted nnn-nn-nnnn" />
                </div>
                </div>',
                '<input autocomplete="off" class="form-control input-sm" id="unittest_ssn_2" name="ssn" pattern="\d{3}[\. -]?\d{2}[\. -]?\d{4}" placeholder="nnn-nn-nnnn" size="11" title="SSN: nnn-nnnn" type="text" x-moz-errormessage="Must be formatted nnn-nn-nnnn" />',
            ),

            // button
            array(
                $controlFactory,
                array(
                    'idPrefix' => 'prefix',
                    'type' => 'button',
                    'name' => 'testBtn',
                    'label' => '<i class="glyphicon glyphicon glyphicon-ok"></i> click me',
                    'attribs' => array('class'=>'btn-primary'),
                    'tagOnly' => true,
                ),
                '<button class="btn btn-default btn-primary" id="prefix_testBtn" name="testBtn" type="button"><i class="glyphicon glyphicon glyphicon-ok"></i> click me</button>',
                '<button class="btn btn-default btn-primary" id="prefix_testBtn_2" name="testBtn" type="button"><i class="glyphicon glyphicon glyphicon-ok"></i> click me</button>',
            ),
            // reset
            array(
                $controlFactory,
                array(
                    'type' => 'reset',
                    'name' => 'testBtn',
                ),
                '<div class="form-group" id="unittest_testBtn_container">
                <div class="controls">
                <button class="btn btn-default" id="unittest_testBtn" name="testBtn" type="reset">Reset</button>
                </div>
                </div>',
                '<button class="btn btn-default" id="unittest_testBtn_2" name="testBtn" type="reset">Reset</button>',
            ),
            array(
                // submit
                $controlFactory,
                array(
                    'type' => 'submit',
                    'name' => 'testBtn',
                ),
                '<div class="form-group" id="unittest_testBtn_3_container">
                <div class="controls">
                <button class="btn btn-default" id="unittest_testBtn_3" name="testBtn" type="submit">Submit</button>
                </div>
                </div>',
                '<button class="btn btn-default" id="unittest_testBtn_4" name="testBtn" type="submit">Submit</button>',
            ),

            array(
                // checkbox
                $controlFactory,
                array(
                    'name' => 'cb1',
                    'type' => 'checkbox',
                    'label' => 'Single Checkbox - no value or options specified',
                ),
                '<div class="form-group" id="unittest_cb1_container">
                <div class="controls">
                <div class="checkbox"><label><input id="unittest_cb1" name="cb1" type="checkbox" value="on" />Single Checkbox - no value or options specified</label></div>
                </div>
                </div>',
                array(
                    'label' => null,
                    'options' => array(
                        array(
                            'attribs' => array(
                                'id' => 'unittest_cb1_2', 'name' => 'cb1', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => false, 'value' => 'on',
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Single Checkbox - no value or options specified',
                            'input' => '<input id="unittest_cb1_2" name="cb1" type="checkbox" value="on" />',
                        )
                    ),
                    'useFieldset' => false,
                ),
            ),
            array(
                $controlFactory,
                array(
                    'name' => 'cb2',
                    'type' => 'checkbox',
                    'label' => 'Single Checkbox - value specified, no options',
                    'value' => 'marshmallow',
                    'disabled' => true,
                    'checked' => true,
                ),
                '<div class="form-group" id="unittest_cb2_container">
                <div class="controls">
                <div class="checkbox disabled"><label><input checked="checked" disabled="disabled" id="unittest_cb2" name="cb2" type="checkbox" value="marshmallow" />Single Checkbox - value specified, no options</label></div>
                </div>
                </div>',
                array(
                    'label' => null,
                    'options' => array(
                        array(
                            'attribs' => array(
                                'id' => 'unittest_cb2_2', 'name' => 'cb2', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => true, 'disabled' => true, 'value' => 'marshmallow',
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                    'disabled',
                                ),
                            ),
                            'label' => 'Single Checkbox - value specified, no options',
                            'input' => '<input checked="checked" disabled="disabled" id="unittest_cb2_2" name="cb2" type="checkbox" value="marshmallow" />',
                        ),
                    ),
                    'useFieldset' => false,
                ),
            ),

            array(
                $controlFactory,
                array(
                    'name' => 'cb4',
                    'type' => 'checkbox',
                    'options' => array(
                        array('value'=>'dingus', 'label'=>'Checkbox Group w/o group label')
                    ),
                ),
                '<div class="form-group" id="unittest_cb4_container">
                <div class="controls">
                <div class="checkbox"><label><input id="unittest_cb4" name="cb4" type="checkbox" value="dingus" />Checkbox Group w/o group label</label></div>
                </div>
                </div>',
                array(
                    'label' => null,
                    'options' => array(
                        array(
                            'attribs' => array(
                                'id' => 'unittest_cb4_2', 'name' => 'cb4', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => false, 'value' => 'dingus',
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Checkbox Group w/o group label',
                            'input' => '<input id="unittest_cb4_2" name="cb4" type="checkbox" value="dingus" />',
                        ),
                    ),
                    'useFieldset' => false,
                ),
            ),

            array(
                $controlFactory,
                array(
                    'name' => 'cb3',
                    'type' => 'checkbox',
                    'label' => 'Single Checkbox - options passed without labels',
                    'options' => array(
                        'Scooby Doo'
                    ),
                ),
                // @todo toss the main label - should be fieldset w/ legend
                '<fieldset class="form-group" id="unittest_cb3_container">
                <legend>Single Checkbox - options passed without labels</legend>
                <div class="controls">
                <div class="checkbox"><label><input id="unittest_cb3" name="cb3" type="checkbox" value="Scooby Doo" />Scooby Doo</label></div>
                </div>
                </fieldset>',
                array(
                    'label' => 'Single Checkbox - options passed without labels',
                    'options' => array(
                        array(
                            'attribs' => array(
                                'id' => 'unittest_cb3_2', 'name' => 'cb3', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => false, 'value' => 'Scooby Doo',
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Scooby Doo',
                            'input' => '<input id="unittest_cb3_2" name="cb3" type="checkbox" value="Scooby Doo" />',
                        ),
                    ),
                    'useFieldset' => true,
                ),
            ),

            array(
                $controlFactory,
                array(
                    'attribs' => array(
                        'name' => 'things1',
                    ),
                    'label' => 'Things',
                    'type' => 'checkbox',
                    'value' => array('Chicken'),    // will be checked
                    'options' => array(
                        'Burrito',
                        'Chicken',
                        array('value' => 'Golf Ball', 'disabled' => true),
                    ),
                ),
                '<fieldset class="form-group" id="unittest_things1_container">
                <legend>Things</legend>
                <div class="controls">
                <div class="checkbox"><label><input id="unittest_things1_1" name="things1[]" type="checkbox" value="Burrito" />Burrito</label></div>
                <div class="checkbox"><label><input checked="checked" id="unittest_things1_2" name="things1[]" type="checkbox" value="Chicken" />Chicken</label></div>
                <div class="checkbox disabled"><label><input disabled="disabled" id="unittest_things1_3" name="things1[]" type="checkbox" value="Golf Ball" />Golf Ball</label></div>
                </div>
                </fieldset>',
                array(
                    'label' => 'Things',
                    'options' => array(
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things1_2_1', 'name' => 'things1[]', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => false, 'value' => 'Burrito',
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Burrito',
                            'input' => '<input id="unittest_things1_2_1" name="things1[]" type="checkbox" value="Burrito" />',
                        ),
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things1_2_2', 'name' => 'things1[]', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => true, 'value' => 'Chicken',
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Chicken',
                            'input' => '<input checked="checked" id="unittest_things1_2_2" name="things1[]" type="checkbox" value="Chicken" />',
                        ),
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things1_2_3', 'name' => 'things1[]', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => false, 'disabled' => true, 'value' => 'Golf Ball',
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                    'disabled',
                                ),
                            ),
                            'label' => 'Golf Ball',
                            'input' => '<input disabled="disabled" id="unittest_things1_2_3" name="things1[]" type="checkbox" value="Golf Ball" />',
                        ),
                    ),
                    'useFieldset' => true,
                ),
            ),

            array(
                $controlFactory,
                array(
                    'type' => 'checkbox',
                    'name' => 'things',
                    'value' => array('Hammer', 'Stick'),
                    'options' => array('Hammer', 'Banana', 'Pillow', 'Desk', 'Stick'),
                    'label' => 'Multiple already selected',
                ),
                '<fieldset class="form-group" id="unittest_things_3_container">
	                <legend>Multiple already selected</legend>
	                <div class="controls">
		                <div class="checkbox"><label><input checked="checked" id="unittest_things_3_1" name="things[]" type="checkbox" value="Hammer" />Hammer</label></div>
		                <div class="checkbox"><label><input id="unittest_things_3_2" name="things[]" type="checkbox" value="Banana" />Banana</label></div>
		                <div class="checkbox"><label><input id="unittest_things_3_3" name="things[]" type="checkbox" value="Pillow" />Pillow</label></div>
		                <div class="checkbox"><label><input id="unittest_things_3_4" name="things[]" type="checkbox" value="Desk" />Desk</label></div>
		                <div class="checkbox"><label><input checked="checked" id="unittest_things_3_5" name="things[]" type="checkbox" value="Stick" />Stick</label></div>
		            </div>
                </fieldset>',
                array(
                    'label' => 'Multiple already selected',
                    'options' => array(
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things_4_1', 'name' => 'things[]', 'value' => 'Hammer', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => true,
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Hammer',
                            'input' => '<input checked="checked" id="unittest_things_4_1" name="things[]" type="checkbox" value="Hammer" />',
                        ),
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things_4_2', 'name' => 'things[]', 'value' => 'Banana', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => false,
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Banana',
                            'input' => '<input id="unittest_things_4_2" name="things[]" type="checkbox" value="Banana" />',
                        ),
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things_4_3', 'name' => 'things[]', 'value' => 'Pillow', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => false,
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Pillow',
                            'input' => '<input id="unittest_things_4_3" name="things[]" type="checkbox" value="Pillow" />',
                        ),
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things_4_4', 'name' => 'things[]', 'value' => 'Desk', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => false,
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Desk',
                            'input' => '<input id="unittest_things_4_4" name="things[]" type="checkbox" value="Desk" />',
                        ),
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things_4_5', 'name' => 'things[]', 'value' => 'Stick', 'type' => 'checkbox', 'required' => false, 'class' => null, 'checked' => true,
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'checkbox',
                                ),
                            ),
                            'label' => 'Stick',
                            'input' => '<input checked="checked" id="unittest_things_4_5" name="things[]" type="checkbox" value="Stick" />',
                        ),
                    ),
                    'useFieldset' => true,
                ),
            ),

            array(
                $controlFactory,
                array(
                    'label' => 'Things (radio)',
                    'type' => 'radio',
                    'name' => 'things2',
                    'value' => 'Other',     // should be selected
                    'options' => array(
                        'This',
                        'That',
                        array('value' => 'Other', 'disabled' => true),
                    ),
                ),
                '<fieldset class="form-group" id="unittest_things2_container">
                    <legend>Things (radio)</legend>
                    <div class="controls">
                        <div class="radio"><label><input id="unittest_things2_1" name="things2" type="radio" value="This" />This</label></div>
                        <div class="radio"><label><input id="unittest_things2_2" name="things2" type="radio" value="That" />That</label></div>
                        <div class="disabled radio"><label><input checked="checked" disabled="disabled" id="unittest_things2_3" name="things2" type="radio" value="Other" />Other</label></div>
                    </div>
                </fieldset>',
                array(
                    'label' => 'Things (radio)',
                    'options' => array(
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things2_2_1', 'name' => 'things2', 'value' => 'This', 'type' => 'radio', 'required' => false, 'class' => null, 'checked' => false,
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'radio',
                                ),
                            ),
                            'label' => 'This',
                            'input' => '<input id="unittest_things2_2_1" name="things2" type="radio" value="This" />',
                        ),
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things2_2_2', 'name' => 'things2', 'value' => 'That', 'type' => 'radio', 'required' => false, 'class' => null, 'checked' => false,
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'radio',
                                ),
                            ),
                            'label' => 'That',
                            'input' => '<input id="unittest_things2_2_2" name="things2" type="radio" value="That" />',
                        ),
                        array(
                            'attribs' => array(
                                'id' => 'unittest_things2_2_3', 'name' => 'things2', 'value' => 'Other', 'type' => 'radio', 'required' => false, 'class' => null, 'checked' => true, 'disabled' => true,
                            ),
                            'attribsLabel' => array(),
                            'attribsPair' => array(
                                'class' => array(
                                    'radio',
                                ),
                            ),
                            'label' => 'Other',
                            'input' => '<input checked="checked" disabled="disabled" id="unittest_things2_2_3" name="things2" type="radio" value="Other" />',
                        ),
                    ),
                    'useFieldset' => true,
                ),
            ),



        );
    }
}
