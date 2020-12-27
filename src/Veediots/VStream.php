<?php

namespace Veediots;

use Veediots\Exceptions\InvalidFileSize;
use Veediots\Exceptions\VideoFailedToOpen;

class VStream
{
    /**
     * The buffer length is the number of mb which are parsed on each
     * request for data.
     */
    public const DEFAULT_BUFFER = 102400; // 100 kb

    /**
     * The buffer length used by the class to chunk the file
     * on each output stream.
     *
     * @var int
     */
    protected $streamBuffer = self::DEFAULT_BUFFER;

    /**
     * The file containing the formatted video data
     *
     * @var resource|bool
     */
    private $video;

    /**
     * The mime content type of the video as expressed by how PHP requires
     * the headers declaration.
     *
     * @var string
     */
    private $type;

    /**
     * The filesize used to determine the overall chunk size based on
     * the streamBuffer.
     *
     * @var int|false
     */
    private $fileSize;

    /**
     * This is an optional parameter extracted from the file meta data
     * for a header configuration.
     *
     * @var int|false
     */
    private $lastModified;

    /**
     * The start point for the stream
     *
     * @var int
     */
    private $streamStart;

    /**
     * The end point for the stream
     *
     * @var int
     */
    private $streamEnd;

    /**
     * @var array[]
     */
    protected $configuration = [
        'streamBufferSize' => [
            'type' => 'is_numeric',
            'value' => self::DEFAULT_BUFFER
        ],
        'maxExecutionTime' => [
            'type' => 'is_numeric',
            'value' => 0
        ]
    ];

    /**
     * VStream constructor. Requires a video file you are looking to stream.
     *
     * Within the constructor the absolute file path will be opened, tested
     * as a valid file, and then basic meta information will be acquired.
     *
     * @param  string  $videoFile
     *
     * @throws VideoFailedToOpen|InvalidFileSize
     */
    public function __construct(string $videoFile)
    {
        // The @symbol is to prevent the E_WARNING provided by fopen.
        $this->video = @fopen($videoFile, 'rb');

        if (! is_resource($this->video)) {
            throw new VideoFailedToOpen('Video file was not found or failed to open.');
        }

        $this->type = mime_content_type($this->video);

        if(! $this->fileSize = filesize($videoFile)) {
            throw new InvalidFileSize('Video filesize could not be determined.');
        }

        $this->lastModified = filemtime($videoFile);

        $this->streamStart  = 0;
        $this->streamEnd    = $this->fileSize - 1;
    }

    /**
     * Sets the chunk size of the stream buffer.
     *
     * @param  int  $buffer
     *
     * @return void
     */
    public function setStreamBufferSize(int $buffer)
    {
        $this->streamBuffer = $buffer;
    }

    /**
     * Plays the stream, designating the mime type
     *
     * @return void
     */
    public function play() : void
    {
        if(ob_get_level() !== 0) {
            ob_clean();
        }

        $this->setHeaders()->showVideo();
    }

    /**
     * Sets the base headers to the type specified.
     *
     * @return self
     */
    private function setHeaders() : self
    {
        header('Content-Type: ' . $this->type);

        // Base configuration
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        if($this->lastModified !== false) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $this->lastModified) . ' GMT');
        }

        header('Accept-Ranges: 0-' . $this->streamEnd);

        $this->setHeaderLength();

        return $this;
    }

    /**
     * Sets the headers for the stream range based on chunk size
     *
     * @return void
     */
    private function setHeaderLength() : void
    {
        if (! isset($_SERVER['HTTP_RANGE'])) {
            header('Content-Length: ' . $this->fileSize);
            return;
        }

        [, $range] = explode('=', $_SERVER['HTTP_RANGE'], 2);

        if (strpos($range, ',') !== false) {
            $this->rangeNotSatisfiable();
        }

        if ($range === '-') {
            $currentStreamStart = $this->fileSize - substr($range, 1);
            $currentStreamEnd   = $this->streamEnd;
        } else {
            [$streamStart, $streamEnd]  = explode('-', $range);

            $currentStreamStart = $streamStart ?? 0;
            $currentStreamEnd   = isset($streamEnd) && is_numeric($streamEnd) ? $streamEnd : $this->streamEnd;
        }

        $currentStreamEnd = ($currentStreamEnd > $this->streamEnd) ? $this->streamEnd : $currentStreamEnd;
        if (
            $currentStreamStart > $currentStreamEnd
            || $currentStreamStart > $this->fileSize - 1
            || $currentStreamEnd >= $this->fileSize
        ) {
            $this->rangeNotSatisfiable();
        }

        $this->streamStart = $currentStreamStart;
        $this->streamEnd   = $currentStreamEnd;

        fseek($this->video, $this->streamStart);
        header('HTTP/1.1 206 Partial Content');
        header('Content-Length: ' . ($this->streamEnd - $this->streamStart + 1));
        header('Content-Range: bytes ' . $this->streamStart . '-' . $this->streamEnd . '/' . $this->fileSize);
    }

    /**
     * Reads the file, ultimately performing the stream.
     *
     * @return void
     */
    private function showVideo() : void
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
    private function rangeNotSatisfiable() : void
    {
        header('HTTP/1.1 416 Requested Range Not Satisfiable');
        header("Content-Range: bytes " . $this->streamStart . "-" . $this->streamEnd . "/" . $this->fileSize);
        exit;
    }
}

class_alias(VStream::class, 'Veediots_VStream');
