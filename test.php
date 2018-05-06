<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/Video.php';

$video = new Video('another_sample.mp4');

$video->play();