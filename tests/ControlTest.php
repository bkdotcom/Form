<?php

use bdk\Form\Control;

/**
 * PHPUnit tests for BuildControl class
 */
class ControlTest extends \PHPUnit\Framework\TestCase
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

            $control = $controlFactory->build($props);
            $this->assertInstanceOf('bdk\Form\Control', $control);
            $html = $control->build();
            $html = \preg_replace('/\n\s+/', "\n", $html);
            $htmlExpect = \preg_replace('/\n\s+/', "\n", $htmlExpect);
            $this->assertSame($htmlExpect, $html, 'Data set '.($i+1));

            $tagOnly = $control->build('tagOnly');
            if (\is_string($tagOnlyExpect)) {
                $tagOnly = \preg_replace('/\n\s+/', "\n", $tagOnly);
                $tagOnlyExpect = \preg_replace('/\n\s+/', "\n", $tagOnlyExpect);
                $this->assertSame($tagOnlyExpect, $tagOnly, 'Data set '.($i+1).' (tag only)');
            } else {
                if ($control->name == 'things2') {
                    // var_dump($tagOnly['options'][2]);
                    // $this->assertArraySubset($tagOnlyExpect['options'][2], $tagOnly['options'][2], false, 'Data set '.($i+1).' (tag only)');
                }
                $this->assertInternalType('array', $tagOnly, 'Data set '.($i+1));
                $this->assertArraySubset($tagOnlyExpect, $tagOnly, false, 'Data set '.($i+1).' (tag only)');
            }
        }
    }
}
