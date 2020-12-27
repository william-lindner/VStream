<?php

use Veediots\VStream;

require __DIR__ . '/vendor/autoload.php';

(new VStream('./videos/another_sample.mp4'))->play();
