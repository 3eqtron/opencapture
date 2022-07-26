<?php

/** Logger class
 *
 * @author Claire Figueras <dev@maarch.org>
 **/
class Logger
{

    /**
     * Array of errors levels
     *
     * @protected
     **/
    protected $error_levels = array('DEBUG' => 0, 'INFO' => 1, 'NOTICE' => 2, 'WARNING' => 3, 'ERROR' => 4);

    /**
     * Maps each handler with its log threshold.
     *
     * @protected
     **/
    protected $mapping;

    /**
     * Minimum log level
     *
     * @protected
     **/
    protected $threshold_level;


    /** Class constructor
     *
     * Inits the threshold level
     *
     * @param $threshold_level (string) Threshold level (set to 'INFO' by default)
     **/
    function __construct($threshold_level = 'WARNING')
    {
        $this->threshold_level = $threshold_level;
        $this->mapping = array_fill(0, count($this->error_levels), array());
    }

    /** Writes error message in current handlers
     *
     * writes only if the error level is greater or equal the threshold level
     *
     * @param $msg (string) Error message
     * @param $error_level (string) Error level (set to 'INFO' by default)
     * @param $error_code (integer) Error code (set to 0 by default)
     **/
    public function write($msg, $error_level = 'WARNING', $error_code = 0, $other_params = array())
    {
        if(!array_key_exists($error_level, $this->error_levels))
        {
            $error_level = 'WARNING';
        }

        for($i=$this->error_levels[$error_level]; $i>=0; $i--)
        {
            foreach($this->mapping[$i] as $handler)
            {
                $handler->write($msg, $error_level, $error_code, $other_params);
            }
        }
    }

    /** Adds a new handler in the current handlers array
     *
     * @param $handler (object) Handler object
     **/
    public function add_handler(&$handler, $error_level = NULL)
    {
        if(!isset($handler))
            return false;

        if(!isset($error_level) || !array_key_exists($error_level, $this->error_levels))
        {
            $error_level = $this->threshold_level;
        }

        $this->mapping[$this->error_levels[$error_level]][] = $handler;
        return true;
    }

    /** Adds a new handler in the current handlers array
     *
     * @param $handler (object) Handler object
     **/
    public function change_handler_log_level(&$handler, $log_level )
    {
        if(!isset($handler) || !isset($log_level))
            return false;

        if(!array_key_exists($log_level, $this->error_levels))
        {
           return false;
        }

        for($i=0; $i<count($this->mapping);$i++)
        {
            for($j=0;$j<count($this->mapping[$i]);$j++)
            {
                if($handler == $this->mapping[$i][$j])
                {
                    unset($this->mapping[$i][$j]);
                }
            }
        }
        $this->mapping = array_values($this->mapping);
        $this->mapping[$this->error_levels[$log_level]][] = $handler;
        return true;
    }


    /** Sets treshold level
     *
     * @param $treshold (string) treshold level
     **/
    public function set_threshold_level($treshold)
    {
        if(isset($treshold) && array_key_exists($treshold, $this->error_levels))
        {
            $this->threshold_level = $treshold;
            return true;
        }
        $this->threshold_level = 'WARNING';
        return false;
    }


    /** Class destructor
     *
     * Calls handlers destructors
     **/
    function __destruct()
    {
        for($i=0; $i<count($this->mapping);$i++)
        {
            foreach($this->mapping[$i] as $handler)
            {
                unset($handler);
            }
        }
    }
}
?>
