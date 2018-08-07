<?php

// backward compatibility
$classMap = array(
    // PHP 5.3 doesn't like leading backslash
    'PHPUnit_Framework_Exception' => 'PHPUnit\Framework\Exception',
    'PHPUnit_Framework_TestCase' => 'PHPUnit\Framework\TestCase',
    'PHPUnit_Framework_TestSuite' => 'PHPUnit\Framework\TestSuite',
);
foreach ($classMap as $old => $new) {
    if (!class_exists($new)) {
        class_alias($old, $new);
    }
}

require __DIR__.'/../vendor/autoload.php';

/*
\bdk\Debug::_setCfg(array(
	'collect' => true,
	'output' => true,
    'onBootstrap' => function (\bdk\PubSub\Event $event) {
        $debug = $event->getSubject();
        if ($debug->getCfg('collect')) {
            $wampPublisher = new \bdk\WampPublisher(array('realm'=>'debug'));
            if ($wampPublisher->connected) {
                $outputWamp = new \bdk\Debug\Output\Wamp($debug, $wampPublisher);
                $debug->setCfg('outputAs', $outputWamp);
            }
        }
    },
));
*/

foreach (glob(__DIR__.'/*.php') as $filename) {
	$basename = basename($filename, '.php');
	if (preg_match('#Test$#', $basename)) {
		continue;
	}
	if ($filename === __FILE__) {
		continue;
	}
	require $filename;
}
