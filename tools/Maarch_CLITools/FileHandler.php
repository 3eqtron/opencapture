<?php
/** FileHandler class
 *
 * Opens a file in the constructor, writes in it and closes it in the destructor
 *
 * @author Claire Figueras <dev@maarch.org>
 **/
class FileHandler
{
    /**
     * File resource
     *
     * @protected
     **/
    protected $log_file;

    /** Class constructor
     *
     * Opens the file stream
     *
     * @param $file_path (string) File path
     **/
    function __construct($file_path)
    {
        $this->log_file = fopen($file_path, 'a');
    }

    /** Writes in file
     *
     * Uses fprintf to write, with the following template :
     * @code
     * [year-month-day Hours:minutes:seconds] Error_level Error_code Error_message
     * [2010-10-21 18:21:35] INFO 1 'File xxx.pdf open'
     * @endcode
     *
     * @param $msg (string) Log message
     * @param $error_level (string) Error level
     * @param $error_code (integer) Error code
     **/
    function write($msg, $error_level, $error_code, $other_params = array())
    {
        return fprintf($this->log_file, "[%s] %s %s '%s'\n", date("Y-m-d H:i:s"), $error_level, $error_code, $msg);
    }

    /** Class destructor
     *
     * Closes the file stream
     **/
    function __destruct()
    {
        //fclose($this->log_file);
    }
}
?>
