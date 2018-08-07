<?php

use bdk\Form\Field;

/**
 * PHPUnit tests for BuildControl class
 */
class FieldTest extends \PHPUnit\Framework\TestCase
{

    public function setUp()
    {
        Field::clearIdCounts();
    }

    /**
     * Test build
     *
     * @return void
     */
    public function testBuild()
    {
        $data = DataProvider::buildProvider();
        foreach ($data as $i => $row) {
            list($buildControl, $fieldFactory, $props, $htmlExpect, $tagOnlyExpect) = $row;

            $field = $fieldFactory->build($props);
            $this->assertInstanceOf(\bdk\Form\Field, $field);
            $html = $field->build();
            $html = preg_replace('/\n\s+/', "\n", $html);
            $htmlExpect = preg_replace('/\n\s+/', "\n", $htmlExpect);
            $this->assertSame($htmlExpect, $html, 'Data set '.($i+1));

            $htmlTagOnly = $field->build('tagOnly');
            $htmlTagOnly = preg_replace('/\n\s+/', "\n", $htmlTagOnly);
            $tagOnlyExpect = preg_replace('/\n\s+/', "\n", $tagOnlyExpect);
            $this->assertSame($tagOnlyExpect, $htmlTagOnly, 'Data set '.($i+1).' (tag only)');
        }
    }

    /**
     * Test build (checkbox & radio)
     *
     * @return void
     *
     * BuildControl $buildControl, FieldFactory $fieldFactory, $props, $htmlExpect, $tagOnlyExpect
     */
    public function testBuildCheckboxRadio()
    {
        $data = DataProvider::buildCheckboxRadioProvider();
        foreach ($data as $i => $row) {
            list($buildControl, $fieldFactory, $props, $htmlExpect, $tagOnlyExpect) = $row;

            $field = $fieldFactory->build($props);
            $this->assertInstanceOf(\bdk\Form\Field, $field);
            $html = $field->build();
            $html = preg_replace('/\n\s+/', "\n", $html);
            $htmlExpect = preg_replace('/\n\s+/', "\n", $htmlExpect);
            $this->assertSame($htmlExpect, $html, 'Data set '.($i+1));

            $tagOnly = $field->build('tagOnly');
            $this->assertInternalType('array', $tagOnly, 'Data set '.($i+1));
            $this->assertArraySubset($tagOnlyExpect, $tagOnly, false, 'Data set '.($i+1).' (tag only)');
        }
    }
}
