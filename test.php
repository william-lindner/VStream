<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/Video.php';

$video = Video::fetch('sample.mp4');

var_dump($video->fileSize);