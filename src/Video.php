<?php

namespace Veediots;

class Video
{
    const DEFAULT_BUFFER    = 102400; // 100 kb
    protected $streamBuffer = self::DEFAULT_BUFFER;
    private $video;
    private $type;
    private $fileSize;
    private $lastModified;
    private $streamStart;
    private $streamEnd;

    /**
     * Requires a video file you are looking to stream. Opens the video file upon construction.
     *
     * @return static
     */
    public function __construct(string $videoFile, string $type = 'video/mp4')
    {
        if (!$this->open($videoFile)) {
            throw new \Exception('Video file not found', 404);
        }
        $this->type         = $type;
        $this->fileSize     = filesize($videoFile);
        $this->lastModified = filemtime($videoFile);
        $this->streamStart  = 0;
        $this->streamEnd    = $this->fileSize - 1;
    }

    /**
     * Sets the chunk size of the stream buffer
     *
     * @return void
     */
    public function setStreamBuffer(int $buffer)
    {
        $this->streamBuffer = $buffer;
    }

    /**
     * Opens the video file at the path provided.
     *
     * @return void
     */
    protected function open(string $videoFile)
    {
        $this->video = @fopen($videoFile, 'rb');
        return is_resource($this->video);
    }

    /**
     * Plays the stream, designating the mime type
     *
     * @return void
     */
    public function play()
    {
        ob_clean();
        $this->_setHeaders();
        $this->_showVideo();
    }

    /**
     * Sets the base headers to the type specified.
     *
     * @return void
     */
    private function _setHeaders()
    {
        header('Content-Type: ' . $this->type);
        header('Cache-Control: no-cache, must-revalidate');
        header("Expires: 0");
        header("Last-Modified: " . gmdate('D, d M Y H:i:s', $this->lastModified) . ' GMT');
        header("Accept-Ranges: 0-" . $this->streamEnd);
        $this->_setHeaderLength();
    }

    /**
     * Sets the headers for the stream range based on chunk size
     *
     * @return void
     */
    private function _setHeaderLength()
    {
        if (!isset($_SERVER['HTTP_RANGE'])) {
            header("Content-Length: " . $this->fileSize);
            return;
        }
        $currentStreamStart = $this->streamStart;
        $currentStreamEnd   = $this->streamEnd;
        list(, $range)      = explode('=', $_SERVER['HTTP_RANGE'], 2);
        if (strpos($range, ',') !== false) {
            $this->_rangeNotSatisfiable();
        }
        if ($range === '-') {
            $currentStreamStart = $this->fileSize - substr($range, 1);
        } else {
            $range              = explode('-', $range);
            $currentStreamStart = $range[0];
            $currentStreamEnd   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $currentStreamEnd;
        }
        $currentStreamEnd = ($currentStreamEnd > $this->streamEnd) ? $this->streamEnd : $currentStreamEnd;
        if ($currentStreamStart > $currentStreamEnd || $currentStreamStart > $this->fileSize - 1 || $currentStreamEnd >= $this->fileSize) {
            $this->_rangeNotSatisfiable();
        }
        $this->streamStart = $currentStreamStart;
        $this->streamEnd   = $currentStreamEnd;
        fseek($this->video, $this->streamStart);
        header('HTTP/1.1 206 Partial Content');
        header("Content-Length: " . ($this->streamEnd - $this->streamStart + 1));
        header("Content-Range: bytes " . $this->streamStart . "-" . $this->streamEnd . "/" . $this->fileSize);
    }

    /**
     * Reads the file, ultimately performing the stream.
     *
     * @return void
     */
    private function _showVideo()
    {
        $endPointer = $this->streamStart;
        set_time_limit(0);
        while (!feof($this->video) && $endPointer <= $this->streamEnd) {
            $bytesToRead = $this->streamBuffer;
            // ensures you never go over the filesize
            if (($endPointer + $bytesToRead) > $this->streamEnd) {
                $bytesToRead = $this->streamEnd - $endPointer + 1;
            }
            echo fread($this->video, $bytesToRead);
            flush();
            $endPointer += $bytesToRead;
        }
        fclose($this->video);
    }

    /**
     * Exits the stream with header indicating the range not satisfiable
     *
     * @return void
     */
    private function _rangeNotSatisfiable()
    {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes " . $this->streamStart . "-" . $this->streamEnd . "/" . $this->fileSize);
        exit;
    }
}
