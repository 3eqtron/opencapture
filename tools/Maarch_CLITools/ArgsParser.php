<?php

class MissingArgumentError extends Exception
{
    public function __construct($arg_name)
    {
        $this->arg_name = $arg_name;
        parent::__construct("Argument \"$arg_name\" is missing!", 1);
    }
}

/** A simple yet powerful class to parse and handle command line arguments.
 *
 * ArgsParser makes it easy to define the command line syntax of your
 * program.
 *
 * You can define arguments with short (ex: -v) and long (ex: --verbose)
 * identifiers.
 *
 * It supports three types of arguments:
 *
 * - Pure arguments, with short or long arguments. Supported syntaxes are:
 *   * --key value or -k value
 *   * --key=value or -k=value
 * - Switches, which translate to boolean values. ex: --verbose or -v.
 * - Positional arguments. Those can be named. All positional arguments
 *   given that are not named will be stored in a special array.
 *
 * Additionally, subcommands are supported. Subcommands must be defined
 * as a new instance of ArgsParser that will be attached to the main one.
 * In consequence, subcommands can have their own sets of arguments.
 *
 * @author Bruno Carlin <bruno.carlin@maarch.org>
 *
 * @todo Support de fonctions de callback à exécuter quand un argument est
 *       rencontré
 **/
class ArgsParser
{

    /** Receives a mapping between arguments identifiers (in short and
     *  long form) and the corresponding argument.
     *
     * @private
     **/
    protected $_mapping = Array();


    /** Hash table that keeps all metadata about arguments.
     *
     * @private
     **/
    protected $_args = Array();


    /** Hash that keeps the given values of each arguments (or the default
     *  value if applicable).
     *
     * @private
     **/
    protected $_options = Array("positional"=>Array());


    /** Records the list of expected positional arguments.
     *
     * @private
     **/
    protected $_positionals = Array();


    /** Internal name of the parser.
     *
     * It is used to differentiate subcommand parsers from the main one.
     *
     * @private
     **/
    protected $_name = "__main__";


    /** Register an argument in the parser.
     *
     * This is the only way to register an argument in the parser.
     * Examples of use :
     * - To add a optional switch to make output more verbose:
     * @code
     * $argsparser = new ArgsParser();
     * $argsparser->add_arg("verbose", "switch", "v", "verbose", False,
     *                      False, "Makes output more verbose");
     * @endcode
     * - To add a mandatory positional argument that contains an output path:
     * @code
     * $argsparser = new ArgsParser();
     * $argsparser->add_arg("output", "positional", null, null, null,
     *                      True, "Output path");
     * @endcode
     *
     * @param $name (string) The name of the argument
     * @param $type (string) The argument's type. Supported values:
     *        argument, switch and positional
     * @param $short (string) The short identifier for the argument
     * @param $long (string) The long identifier for the argument
     * @param $default (mixed) The default value of the argument
     * @param $mandatory (bool) Wether the argument must be given or not
     * @param $help (string) Description of the argument. Will be used to
     *        generate help
     * @return null
     *
     * @todo Argument validation
     **/
    function add_arg($name, $params=Array())
    {
        $default_params = Array(
            "short" => null,
            "long" => null,
            "default" => null,
            "help" => "",
            "mandatory" => false,
        );
        $params = array_merge($default_params, $params);
        $params["type"] = "argument";
        $params["name"] = $name;
        $params["mandatory"] = ((bool) $params["mandatory"]) ? true : false;

        if ($params["short"] !== null) {
            $this->_mapping[$params["short"]] = $name;
        }
        if ($params["long"] !== null) {
            $this->_mapping[$params["long"]] = $name;
        }

        if ($params['default'] !== null) {
            $this->_options[$name] = $params['default'];
        }
        $this->_args[$name] = $params;
    }



    public function add_switch($name, $params=Array())
    {
        $default_params = array(
            "short" => null,
            "long" => null,
            "default" => false,
            "help" => "",
        );
        $params = array_merge($default_params, $params);
        $params["type"] = "switch";
        $params["name"] = $name;
        $params["mandatory"] = false;

        if ($params["short"] !== null) {
            $this->_mapping[$params["short"]] = $name;
        }
        if ($params["long"] !== null) {
            $this->_mapping[$params["long"]] = $name;
        }

        $this->_options[$name] = $params['default'];
        $this->_args[$name] = $params;
    }



    public function add_positional($name, $params=Array())
    {
        $default_params = array(
            "default" => null,
            "help" => "",
            "mandatory" => false,
        );
        $params = array_merge($default_params, $params);
        $params["type"] = "positional";
        $params["name"] = $name;

        if ($params["default"] !== null) {
            $this->_options[$name] = $params['default'];
        }

        $this->_positionals[] = $name;
        $this->_args[$name] = $params;
    }


    /** Register a subcommand to the parser
     *
     * This allows to define and use subcommands on the command line. For
     * example:
     * @code
     * $ program.php command -o /path/to/output/file
     * @endcode
     *
     * Those commands can be invoked with both the command name and its aliases.
     * Let's imagine a wrapper for the svn program. You can define the
     * following:
     * @code
     * $main_parser = new ArgsParser();
     * $main_parser-> add_arg( ... );
     *
     * $checkout_parser = new ArgsParser();
     * $checkout_parser->add_arg( ... );
     * $main_parser->add_command("checkout", Array("co", "get"),
     *                           $checkout_parser,
     *                           "Checkouts the given SVN repository");
     * @endcode
     *
     * You can then use this command the following ways:
     * @code
     * $ svn.php checkout svn://url/to/repository
     * $ svn.php co svn://url/to/repository
     * $ svn.php get svn://url/to/repository
     * @endcode
     *
     * @param $name (string) Main name of the command
     * @param $aliases (array) Aliases for the command
     * @param $parser (ArgsParser) An instance of ArgsParser that declares
     *        the specific arguments of the subcommand
     * @param $help (string) Description of the argument. Will be used to
     *        generate help
     * @returns null
     **/
    function add_command($name, $aliases, &$parser, $help="")
    {
        # the internal name of the subparser is changed
        $parser->_name=$name;

        # we register the command as an argument
        $this->_args[$name] = Array(
            "type" => "command",
            "help" => $help,
            "name" => $name,
            "aliases" => $aliases,
            "parser" => $parser,
        );

        # we register the command name and its aliases in the mapping hash
        $this->_mapping[$name] = $name;
        foreach ($aliases as $alias) {
            $this->_mapping[$alias] = $name;
        }
    }


    /** Gets the actual command line argument and parses them.
     *
     * This is the core of the class.
     *
     * It takes as input a list of token taken directly from command line.
     * @code
     * $ program.php --arg1 "value of arg1" -k=v path/to/file --switch
     * @endcode
     * must be translated as
     * @code
     * Array("program.php", "--arg1", "value of arg1", "-k=v",
     *       "path/to/file", "--switch");
     * @endcode
     *
     * This list will be processed and an array containing the options
     * will be return.
     * The returned array has the following structure:
     * @code
     * Array(
     *   "executable" => "program.php",
     *   "positional" => Array("list of", "non-declared",
     *                         "positional arguments"),
     *   "name of arg1" => "value of arg1",
     *   "name of k" => "v",
     *   "non-given arg" => "default value",
     *   "path as positional arg" => "path/to/file",
     *   "switch" => True
     * )
     * @endcode
     *
     * If a subcommand is given on the command line, a "subcommand" item
     * will be added in the output array. It will contain an array
     * defined as following:
     * @code
     * Array(
     *   "opts" => Array( ... ), // array returned by the subcommand parser
     *                              parse_args method containing the
     *                              subcommand specific arguments
     *   "name" => "name of the command"
     * )
     * @endcode
     *
     * @param $args (array) The list of arguments passed on the command
     *        line (typically $GLOBALS["argv"])
     * @returns (array) The list of processed options
     *
     * @todo vérifier que tous les arguments positionnels obligatoires
     *       ont été passés
     * @todo vérifier qu'il n'ait pas été donné trop d'arguments
     *       positionnels
     * @todo Support de la concaténation des arguments courts
     *       (p-ê que les switch)
     **/
    function parse_args($args)
    {

        # store the name of the executable. first item in the list
        $this->_options["executable"] = array_shift($args);

        # We get a copy of the array containing declared positional arguments.
        # We will modify this array in this method, but we need to keep the
        # original one to display help if an error occurs during
        # arguments parsing.
        $positionals_cp = $this->_positionals;

        if ($this->_handle_help($args) === False){
            return False;
        }

        # the processing begins
        for ($i = 0; $i < count($args); $i++) {

            # we begins the item as a key. several cases are considered:
            # if it begins by dashes (-):
            $arg = ltrim($args[$i], "-");
            # if it contains an equal sign (i.e. key=value)
            $tmp = explode("=", $arg, 2);
            $arg_key = $tmp[0];

            /*# the -h and --help arguments are automatically handled
            if (in_array($arg_key, Array("help", "h"))) {
                # If a subcommand is declared, the subcommand help is
                # automatically handled, and "help subcommand" must print
                # the usage of the subcommand
                $command_name = $args[$i+1];
                if (array_key_exists($command_name, $this->_mapping)) {
                    $command_name = $this->_mapping[$command_name];
                    if ($this->_args[$command_name]["type"]=="command") {
                        $this->_args[$command_name]["parser"]->usage();
                        exit();
                    }
                }
                print $this->usage();
                exit();
            }*/

            # We verify if the argument has been registered
            if (array_key_exists($arg_key, $this->_mapping)) {

                $arg_name = $this->_mapping[$arg_key];
                $arg_type = $this->_args[$arg_name]["type"];

                # Different actions will be taken according to the type
                # of argument
                switch($arg_type) {
                    case "switch":
                        # we set a switch to True
                        $this->_options[$arg_name] = true;
                        break;
                    case "argument":
                        # for an argument, we verify if the value is in
                        # in the same token...
                        if (count($tmp) == 2) {
                            $this->_options[$arg_name] = $tmp[1];
                            break;
                        }
                        # ...or in the next item of arg list
                        if (!isset($args[$i+1]) || substr($args[$i+1], 0, 1) == "-") {
                            throw new MissingArgumentError($arg_name);
                        }
                        $this->_options[$arg_name] = $args[++$i];
                        break;
                    case "command":
                        # if it is a command, argument parsing here stops,
                        # and all remaining arguments are passed to the
                        # command parser
                        $parser_opts = array_slice($args, $i);
                        $parser = $this->_args[$arg_name]["parser"];
                        $this->_options["command"] = Array(
                            "opts" => $parser->parse_args($parser_opts),
                            "name" => $this->_args[$arg_name]["name"]
                        );
                        break 2;
                }
            # If the argument has not been registered, we store it as an
            # anonymous positional argument
            } else {
                if ($positionals_cp) {
                    $this->_options[array_shift($positionals_cp)] = $args[$i];
                } else {
                    $this->_options["positional"][] = $arg;
                }
            }
        }

        $this->_check_mandatory_args();

        #echo "mapping : ";print_r($this->_mapping);
        #echo "args : ";print_r($this->_args);
        #echo "options : ";print_r($this->_options);
        #echo "commands : ";print_r($this->commands);
        #echo "positionals : ";print_r($this->_positionals);
        #exit;

        # We finally return the option array with the values given on
        # the command line
        return $this->_options;
    }

    private static function  is_possible_cmd($str)
    {
        return (substr($str,0,1) != "-");
    }

    protected function _handle_help($args)
    {
        if (count(array_intersect($args, array("-h", "--help", "help"))) == 0) {
            return True;
        }
        $arg_list = array_diff($args, array("-h", "--help", "help"));

        # the use of array_value here is necessary to reindex the resulting
        # array from 0: array_filter preserves the keys
        $arg_list = array_values(
                        array_filter($arg_list,
                                     Array("ArgsParser", "is_possible_cmd")));

        if (count($arg_list) == 0) {
            print $this->usage();
            return False;
        }
        $command_name = $arg_list[0];
        if (array_key_exists($command_name, $this->_mapping)) {
            $command_name = $this->_mapping[$command_name];
            print $this->_args[$command_name]["parser"]->usage();
            return False;
        }
        print $this->usage();
        return False;
    }


    /** Automatically generates usage for the program and exits
     *
     * This method is automatically called in the following case :
     * - no value is given for an argument
     * - the -h or --help switches are given on the command line
     * - the "help" command is given.
     *
     * It can also be invoked directly :
     * @code
     * $argsparser = new ArgsParser();
     * $argsparser->usage();
     * @endcode
     **/
    function usage()
    {
        $this->add_switch("help",
                          Array(
                             "short"=> "h",
                             "long" => "help",
                             "help" => "Display help and exits"));

        $signature = Array("\n","Usage:",$_SERVER["PHP_SELF"]);

        # if this instance is a subcommand, display the name of the
        # command in the signature
        if ($this->_name !== "__main__") {
            $signature[] = $this->_name;
        }
        $options = Array();
        $positionals = Array();
        $commands = Array();
        foreach ($this->_args as $name => $opts) {
            switch($opts["type"]){
                case "argument":
                case "switch":
                    $tmp = ($opts["short"] !== null) ?
                                "-".$opts["short"] : "--".$opts["long"];
                    if ($opts["type"] == "argument") {
                        $tmp .= " ".$name;
                    }
                    if (!$opts["mandatory"]) {
                        $tmp = "[".$tmp."]";
                    }
                    $signature[] = $tmp;

                    $opts_desc = Array();
                    if ($opts["short"]) {
                        $opts_desc[] =  "-".$opts["short"];
                    }
                    if ($opts["long"]) {
                        $opts_desc[] =  "--".$opts["long"];
                    }
                    $opts_desc = implode(",", $opts_desc);
                    $opts_desc .= ($opts["type"] == "argument") ?
                                        " <".$opts["name"].">":"";

                    $message = &$options;
                    break;

                case "positional":
                    $signature[] = $opts["mandatory"] ?
                                    $opts["name"] : "[".$opts["name"]."]";
                    $opts_desc = $opts["name"];
                    $message = &$positionals;
                    break;

                case "command":
                    $opts_desc = $opts["name"];
                    $message = &$commands;
                    break;
            }

            $tmp = explode("\n", wordwrap($opts["help"], 50, "\n"));

            $opts_default = array_key_exists("default", $opts) ?
                                $opts["default"] : null;
            if ($opts_default !== null) {
                if ($opts_default === false) {
                    $opts_default="false";
                }
                $tmp[] = "(default: " . $opts_default . ")";
            }

            $tmp[0] = sprintf("%-25s   ", $opts_desc).$tmp[0];
            for ($i=1; $i<count($tmp); $i++) {
                $tmp[$i] = sprintf("%28s", " ").$tmp[$i];
            }
            array_splice($message, count($message), 0, $tmp);
        }

        if (count($commands)) {
            $signature[] = "COMMAND";
        }

        $msg = implode(" ", $signature);
        if (count($positionals)) {
           $msg .= "\n\nARGUMENTS\n";
            $msg .= implode("\n", $positionals);
        }
        if (count($options)) {
            $msg .= "\n\nOPTIONS\n";
            $msg .= implode("\n", $options);
        }
        if (count($commands)) {
            $msg .= "\n\nCOMMANDS\n";
            $msg .= implode("\n", $commands);
            $msg .= "\n".sprintf("%-25s   ", "help")."Display help and exit.";
            $msg .= "\n\nType 'help <command>' to display usage for a specific "
                    ."command";
        }
        return $msg."\n";
    }

    /** Verifies that all mandatory arguments have been given.
     *
     * @protected
     */
    protected function _check_mandatory_args()
    {
        foreach ($this->_args as $name => $metadata) {
            if ($metadata['type'] != "command"
                    && $metadata['mandatory'] === True) {
                if (!array_key_exists($name, $this->_options)) {
                    throw new MissingArgumentError($name);
                }
            }
        }
        return True;
    }

}
