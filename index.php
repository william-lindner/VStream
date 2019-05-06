<?php

namespace Veediots;

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/Video.php';

$video = new VStream(__DIR__ . '/videos/another_sample.mp4');

$video->play();
