<?php
/*

EXAMPLE: $video = Video::play('');

 */

class Video
{
  const VIDEO_PATH = '/../files/videos/calls/';
  const BUFFER     = 102400; // 100 kb

  public $state = null;
  public $error = null;

  private $video;

  public $fileSize = null;
  public $streamBlocks = null;
  public $lastModified = null;

  final private function __construct()
  {
    // no instantiation directly - must fetch file
  }

  public static function fetch($fileName = null, $relativePath = true) {
    // check the filename integrity
    if (!$fileName || !is_string($fileName)) {
      throw new Exception('No file name provided.');
    }
    // set the path to the video and open it in read only state (binary)
    $filePath = $_SERVER['DOCUMENT_ROOT'] . self::VIDEO_PATH . $fileName;
    $instance = new self;
    if (!$instance->video = fopen($filePath, 'rb')) {
      throw new Exception('Could not open video file for reading.');
    }
    
    // setup information about the file and return the instance
    $instance->fileSize = filesize($filePath);
    $instance->lastModified = filemtime($filePath);

    return $instance;

  }

  /*
  * Start streaming video content
  */
  public function play($fileName = null, $relative = true)
  {
    // flush everything if possible and setup the basic headers
    ob_get_clean();
    header("Content-Type: video/mp4"); // modify for other video types later
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: 0");
    header("Last-Modified: " . gmdate('D, d M Y H:i:s', @filemtime(self::$file_path)) . ' GMT');

    $streamStartPoint  = 0;
    $streamEndPoint    = $this->$fileSize - 1;

    // set the range in the header
    header("Accept-Ranges: 0-" . $streamEndPoint);

    if (isset($_SERVER['HTTP_RANGE'])) {
      
      $currentStreamStart = $streamStartPoint;
      $currentStreamEnd   = $streamEndPoint;
  
      list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
      if (strpos($range, ',') !== false) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes " . $streamStartPoint . "-" . $streamEndPoint. "/" . $this->fileSize);
        exit;
      }
      if ($range == '-') {
        $currentStreamStart = self::$fileSize - substr($range, 1);
      } else {
        $range   = explode('-', $range);
        $currentStreamStart = $range[0];
  
        $currentStreamEnd = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $currentStreamEnd;
      }
      $currentStreamEnd = ($currentStreamEnd > $streamEndPoint) ? $streamEndPoint: $currentStreamEnd;
      if ($currentStreamStart > $currentStreamEnd || $currentStreamStart > self::$fileSize - 1 || $currentStreamEnd >= self::$fileSize) {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes " . $streamStartPoint . "-" . $streamEndPoint. "/" . self::$fileSize);
        exit;
      }
      $streamStartPoint = $currentStreamStart;
      $streamEndPoint  = $currentStreamEnd;
      $length      = $streamEndPoint- $streamStartPoint + 1;
      fseek(self::$stream, $streamStartPoint);
      header('HTTP/1.1 206 Partial Content');
      header("Content-Length: " . $length);
      header("Content-Range: bytes " . $streamStartPoint . "-" . $streamEndPoint. "/" . self::$fileSize);
      } else {
      header("Content-Length: " . self::$fileSize);
      }
  
      self::set_header();
  
      $i = $streamStartPoint;
      set_time_limit(0);
      while (!feof($stream) && $i <= $streamEndPoint) {
      $bytesToRead = self::$buffer;
      if (($i + $bytesToRead) > $streamEndPoint) {
        $bytesToRead = $streamEndPoint- $i + 1;
      }
      $data = fread($stream, $bytesToRead);
      echo $data;
      flush();
      $i += $bytesToRead;
      }
  
      fclose($stream);
 }
}
