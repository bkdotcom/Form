<?php

use bdk\Form\Field;

/**
 * PHPUnit tests for BuildControl class
 */
class BuildControlTest extends \PHPUnit\Framework\TestCase
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

            $html = $buildControl->build($props);
            $html = preg_replace('/\n\s+/', "\n", $html);
            $htmlExpect = preg_replace('/\n\s+/', "\n", $htmlExpect);
            $this->assertSame($htmlExpect, $html, 'Data set '.($i+1));

            $props['tagOnly'] = true;
            $htmlTagOnly = $buildControl->build($props);
            $htmlTagOnly = preg_replace('/\n\s+/', "\n", $htmlTagOnly);
            $tagOnlyExpect = preg_replace('/\n\s+/', "\n", $tagOnlyExpect);
            $this->assertSame($tagOnlyExpect, $htmlTagOnly, 'Data set '.($i+1).' (tag only)');
        }

        // $field = $fieldFactory->build($props);
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

            $html = $buildControl->build($props);
            $html = preg_replace('/\n\s+/', "\n", $html);
            $htmlExpect = preg_replace('/\n\s+/', "\n", $htmlExpect);
            $this->assertSame($htmlExpect, $html, 'Data set '.($i+1));

            $props['tagOnly'] = true;
            $tagOnly = $buildControl->build($props);

            $this->assertInternalType('array', $tagOnly, 'Data set '.($i+1));
            if ($i == 2) {
                // print_r($tagOnlyExpect);
                // print_r(array_intersect_key($tagOnly, $tagOnlyExpect));
            }
            $this->assertArraySubset($tagOnlyExpect, $tagOnly, false, 'Data set '.($i+1).' (tag only)');
        }
    }
}
