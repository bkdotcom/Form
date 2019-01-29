<?php

use bdk\Form\Control;

/**
 * PHPUnit tests for BuildControl class
 */
class BuildControlTest extends \PHPUnit\Framework\TestCase
{

    public function setUp()
    {
        Control::clearIdCounts();
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
            list($controlFactory, $props, $htmlExpect, $tagOnlyExpect) = $row;

            $html = $controlFactory->controlBuilder->build($props);
            $html = preg_replace('/\n\s+/', "\n", $html);
            $htmlExpect = preg_replace('/\n\s+/', "\n", $htmlExpect);
            $this->assertSame($htmlExpect, $html, 'Data set '.($i+1));

            $props['tagOnly'] = true;
            $tagOnly = $controlFactory->controlBuilder->build($props);
            if (is_string($tagOnlyExpect)) {
                $tagOnly = preg_replace('/\n\s+/', "\n", $tagOnly);
                $tagOnlyExpect = preg_replace('/\n\s+/', "\n", $tagOnlyExpect);
                $this->assertSame($tagOnlyExpect, $tagOnly, 'Data set '.($i+1).' (tag only)');
            } else {
                $this->assertInternalType('array', $tagOnly, 'Data set '.($i+1));
                $this->assertArraySubset($tagOnlyExpect, $tagOnly, false, 'Data set '.($i+1).' (tag only)');
            }
        }
    }
}
