<?php
/** ConsoleHandler class 
 * 
 * Opens standard and error outputs. Writes error messages only in error output and all others messages in standard output
 *
 * @author Claire Figueras <dev@maarch.org>
 **/
class ConsoleHandler
{
	/** 
	 * Standard output (displays all infos but errors)
     *
     * @protected
     **/
	protected $stdout;
	
	/** 
	 * Error output (displays only errors)
     *
     * @protected
     **/
	protected $stderr;
	
	/** Class constructor
     *
     * Opens the standard and errors outputs 
     **/
	function __construct()
	{
		$this->stdout = fopen('php://stdout', 'a');	
		$this->stderr = fopen('php://stderr', 'a');	
	}
	
	/** Writes error message in right output (standard or error)
     *
     * Uses fprintf to write, with the following template :
     * @code
     * [year-month-day Hours:minutes:seconds] Error_level Error_code Error_message
     * [2010-10-21 18:21:35] INFO 1 'File xxx.pdf open'
     * 
	 * @param $msg (string) Log message
	 * @param $error_level (string) Error level
	 * @param $error_code (integer) Error code
     **/
	function write($msg, $error_level, $error_code, $other_params = array())
	{
		if($error_level == 'ERROR')
		{
			return fprintf($this->stderr, "[%s] %s %s '%s'\n", date("Y-m-d H:i:s"), $error_level, $error_code, $msg);
		}
		return fprintf($this->stdout, "[%s] %s %s '%s'\n", date("Y-m-d H:i:s"), $error_level, $error_code, $msg);
	}
	
	/** Class destructor
     *
     * Closes the outputs stream
     **/
	function __destruct()
	{
		//fclose($this->stderr);
		//fclose($this->stdout);
	}
}
?>
