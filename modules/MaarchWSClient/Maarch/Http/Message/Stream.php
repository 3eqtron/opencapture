<?php
/*
 * Copyright (C) 2015 Maarch
 *
 * This file is part of Maarch.
 *
 * Maarch is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Maarch is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Maarch. If not, see <http://www.gnu.org/licenses/>.
 */
namespace Maarch\Http\Message;
/**
 * Describes a data stream.
 * 
 * @package Maarch
 */
class Stream
    implements \Psr\Http\Message\StreamInterface
{
    /**
     * @var resource The stream resource handler
     */
    protected $handler;

    /**
     * Construct the wrapper
     * @param resource $handler The handler
     */
    public function __construct($handler)
    {
        $this->handler = $handler;
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getContents();
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        return fclose($this->handler);
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $resource = $this->handler;

        unset($this->handler);

        return $resource;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        return ftell($this->handler);
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->handler);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->handler->getMetadata('seekable');
    }

    /**
     * Seek to a position in the stream.
     * 
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * 
     * @return integer
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        return fseek($this->handler, $offset, $whence);
    }

    /**
     * Seek to the beginning of the stream.
     *
     * @return bool
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        if (fseek($this->handler, 0) !== 0) {
            // php://input is not seekable even if told so in metadata
            // To rewind, open a new stream
            fclose($this->handler);
            $this->handler = fopen($this->getMetadata('uri'), 'r');
        }
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        $mode = $this->getMetadata('mode');

        return $mode[0] == 'w' || $mode[1] == '+';;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * 
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        return fwrite($this->handler, $string);
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        $mode = $this->handler->getMetadata('mode');

        return $mode[0] == 'r' || $mode[1] == '+';;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * 
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * 
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        return fread($this->handler, $length);
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read.
     * @throws \RuntimeException if error occurs while reading.
     */
    public function getContents()
    {
        return stream_get_contents($this->handler);
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * @param string $key Specific metadata to retrieve.
     * 
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        $metadata = stream_get_meta_data($this->handler);

        if (!$key) {
            return $metadata;
        }

        if (isset($metadata[$key])) {
            return $metadata[$key];
        }
    }
}
