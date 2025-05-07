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
 * Describes an uploaded file.
 * 
 * @package Maarch
 */
class UploadedFile
    implements \Psr\Http\Message\UploadedFileInterface
{
    /**
     * @var string The filename
     */
    protected $name;

    /**
     * @var string The file mime type
     */
    protected $type;

    /**
     * @var string The file temporary umpload location
     */
    protected $tmpname;

    /**
     * @var int The error code
     */
    protected $error;

    /**
     * @var int The file size
     */
    protected $size;

    /**
     * Construct the uploaded file
     * @param string $name    The name
     * @param string $type    The type
     * @param int    $size    The size
     * @param string $tmpname The tmpname
     * @param int    $error   The error
     */
    public function __construct($name, $type, $size, $tmpname, $error=0)
    {
        $this->name = $name;
        $this->type = $type;
        $this->size = $size;
        $this->tmpname = $tmpname;
        $this->error = $error;
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * 
     * @throws \RuntimeException in cases when no stream is available.
     * @throws \RuntimeException in cases when no stream can be created.
     */
    public function getStream()
    {
        $fp = fopen("php://temp", 'r+');
        fputs($fp, file_get_contents($this->tmpname));
        rewind($fp);
        
        return new Stream($fp);
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @param string $targetPath Path to which to move the uploaded file.
     * 
     * @throws \InvalidArgumentException if the $targetPath specified is invalid.
     * @throws \RuntimeException on any error during the move operation.
     * @throws \RuntimeException on the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        if (is_uploaded_file($this->tmpname)) {
            move_uploaded_file($this->tmpname, $targetPath);
        }
    }

    /**
     * Retrieve the file size.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename()
    {
        return $this->name;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType()
    {
        return $this->type;
    }
}
