<?php

use Veediots\VStream;

require __DIR__ . '/vendor/autoload.php';

(new VStream(__DIR__ . '/videos/another_sample.mp4'))->play();
