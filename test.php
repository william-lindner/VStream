<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/Video.php';

$video = new Video('another_sample.mp4');

$video->fetch('sample.mp4');
//var_dump($video);

$video->play();