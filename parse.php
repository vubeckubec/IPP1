<?php
/*
Viktor Kubec, xkubec03, 2BIT, 2022
Parser of IPPcode22
Used plain PHP and for XML generation used XMLWriter
*/

//Assignment specificly said we need this, so I included it.
ini_set('display_errors', 'stderr');

//Constants representing exit codes.
define("ERR_BAD_ARGS", 10);
define("ERR_BAD_HEADER", 21);
define("ERR_BAD_SOURCE_CODE", 22);
define("ERR_LEX_SYNTAX_OTHER", 23);
define("ERR_INTERNAL", 99);
define("CORRECT_EXECUTION", 0);

$instruction_order = 1;

//Asociative array which contains all of the valid instructions.
$valid_instructions_list = array(
    "MOVE" => array("var", "symb"),
    "CREATEFRAME" => array(),
    "PUSHFRAME" => array(),
    "POPFRAME" => array(),
    "DEFVAR" => array("var"),
    "CALL" => array("label"),
    "RETURN" => array(),
    "PUSHS" => array("symb"),
    "POPS" => array("var"),
    "ADD" => array("var", "symb", "symb"),
    "SUB" => array("var", "symb", "symb"),
    "MUL" => array("var", "symb", "symb"),
    "IDIV" => array("var", "symb", "symb"),
    "LT" => array("var", "symb", "symb"),
    "GT" => array("var", "symb", "symb"),
    "EQ" => array("var", "symb", "symb"),
    "AND" => array("var", "symb", "symb"),
    "OR" => array("var", "symb", "symb"),
    "NOT" => array("var", "symb"),
    "INT2CHAR" => array("var", "symb"),
    "STRI2INT" => array("var", "symb", "symb"),
    "READ" => array("var", "type"),
    "WRITE" => array("symb"),
    "CONCAT" => array("var", "symb", "symb"),
    "STRLEN" => array("var", "symb"),
    "GETCHAR" => array("var", "symb", "symb"),
    "SETCHAR" => array("var", "symb", "symb"),
    "TYPE" => array("var", "symb"),
    "LABEL" => array("label"),
    "JUMP" => array("label"),
    "JUMPIFEQ" => array("label", "symb", "symb"),
    "JUMPIFNEQ" => array("label", "symb", "symb"),
    "EXIT" => array("symb"),
    "DPRINT" => array("symb"),
    "BREAK" => array()
);

//Since the assignment specified, that only valid argument for parser is --help, value of argc > 2 means error.
//Terminated with exit code 10.
if($argc > 2)
{
    fprintf(STDERR, "Too many arguments were passed to program. Please use --help to find out how to use parser correctly. Program shutting down.\n");
    exit(ERR_BAD_ARGS);
}

//Program got exactly two arguments and the second one is --help. Program prints out help for the user.
//Correct execution of program, returning code 0.
if($argc === 2 && $argv[1] === "--help")
{
    fprintf(STDOUT, "Correct way of using parser: php8.1 parser.php < [file_name]\n");
    exit(CORRECT_EXECUTION);
}

//Program got exactly two arguments, but the second one is invalid/typo in it.
//Terminated with exit code 10.
if($argc === 2 && $argv[1] !== "--help")
{
    fprintf(STDERR, "Program got an argument which is either invalid or has typo in it. Program shutting down.\n");
    exit(ERR_BAD_ARGS);    
}

//Since there can be whatever number of comments before the header itself, 
//program reads until it either reaches the end of file or the line is not comment or empty lien, since the header has to be first instruction in source code.
do
{
    $cur_line = fgets(STDIN);
    if('' === trim($cur_line) || $cur_line[0] === '#')
    {
        continue;
    }
    else
    {
        break;
    }
}while(!feof(STDIN));

//Once we find first line, that is not starting by # (comment), we replace any comments that could be anywhere else on this line.
$cur_line = preg_replace('/(\#.*)/', '', $cur_line);
//Trim whitespaces and convert the string to lower case, since the header .IPPcode22 is case insensitive. 
//If the line we read is not the header, the program is terminated with exit code 21.
if (strtolower(trim($cur_line)) !== ".ippcode22") 
{
    fprintf(STDERR, "Required program header .IPPcode22 was not found in source code. Program shutting down.\n");
    exit(ERR_BAD_HEADER);
}

//Creating new XMLWriter here.
$new_xml_writer = xmlwriter_open_memory();
//Checking XMLWriter creation for error.
if(!$new_xml_writer)
{
    fprintf(STDERR, "Error occurred while creating a new XMLWriter. Program shutting down.\n");
    exit(ERR_INTERNAL);
}
//Setting indent.
xmlwriter_set_indent($new_xml_writer, 1);
$set_indent_res = xmlwriter_set_indent_string($new_xml_writer, ' ');
//Checking indent setting for error.
if(!$set_indent_res)
{
    fprintf(STDERR, "Error occurred while setting indent string. Program shutting down.\n");
    exit(ERR_INTERNAL);
}
//Creating XML header and the main program element with language attribute.
//Rest of the XML will be created by the main part of program, once the individual lines of IPPcode22 are checked.
xmlwriter_start_document($new_xml_writer, '1.0', 'UTF-8');
xmlwriter_start_element($new_xml_writer, 'program');
xmlwriter_start_attribute($new_xml_writer, 'language');
xmlwriter_text($new_xml_writer, 'IPPcode22');
xmlwriter_end_attribute($new_xml_writer);

//Function checks current instruction. Args of function -> $instruction holds instruction name, $arguments array of instruction arguments.
//Checks if instruction name is correct, if not program tells user in which instruction is the problem by telling him the order of instruction.
//Function also checks wheter the number of arguments is correct, if not user is informed.
//In both cases program is terminated with appropriate exit codes.
function check_instruction_validity($instruction, $arguments)
{
    global $valid_instructions_list;
    global $instruction_order;

    if(!array_key_exists($instruction, $valid_instructions_list))
    {
        fprintf(STDERR, "Lexical error in instruction name. Instruction order is: $instruction_order. Program is shutting down.\n");
        exit(ERR_BAD_SOURCE_CODE);
    }

    if(count($valid_instructions_list[$instruction]) != count($arguments))
    {
        fprintf(STDERR, "Incorrect number of arguments for instruction with order: $instruction_order. Program is shutting down.\n");
        exit(ERR_LEX_SYNTAX_OTHER);
    }
}

//Function checks current argument.
//In cur_arg is the argument we check.
//In correct_cur_arg variable is type the current argument should be, we got this information from our associative array.
//Function goes through switch and checks wheter our current argument is as should be, if not program is terminated with appropriate exit code.
function argument_handler($cur_arg, $correct_cur_arg)
{
    global $instruction_order;
    $cur_arg_type = "";
    $cur_arg_value = "";
    switch($correct_cur_arg)
    {
        case "label":
            if(preg_match('/(^[a-zA-Z-_&$*%?!][a-z0-9A-Z-_&$*%!?]*$)/', $cur_arg))
            {
                $cur_arg_type = "label";
                $cur_arg_value = $cur_arg;   
            }
            else
            {
                fprintf(STDERR, "Error in argument of instruction with order: $instruction_order. Expected type of argument is label. Check syntax of label name. Program shutting down.\n");
                exit(ERR_LEX_SYNTAX_OTHER);
            }
            break;

        case "type":
            if(preg_match('/^((int)|(bool)|(nil)|(string))$/', $cur_arg))
            {
                $cur_arg_type = "type";
                $cur_arg_value = $cur_arg;
            }
            else
            {
                fprintf(STDERR, "Error in argument of instruction with order: $instruction_order. Expected type of argument is type. Check syntax of type. Program shutting down.\n");
                exit(ERR_LEX_SYNTAX_OTHER);
            }
            break;

        case "var":
            if(preg_match('/(^(GF|LF|TF)@[a-zA-Z-_&$*%?!][a-z0-9A-Z-_&$*%!?]*$)/', $cur_arg))
            {
                $cur_arg_type = "var";
                $cur_arg_value = $cur_arg;
            }
            else
            {
                fprintf(STDERR, "Error in argument of instruction with order: $instruction_order. Expected type of argument is var. Check syntax of var name. Program shutting down.\n");
                exit(ERR_LEX_SYNTAX_OTHER);
            }
            break;

        case "symb":
            if(preg_match('/(^(bool)@(true|false)$)/', $cur_arg))
            {
                $cur_arg_type = "bool";
                $cur_arg_value = substr($cur_arg, strpos($cur_arg, "@") + 1);
                break;
            }
            if(preg_match('/(^int@[+-]?([\d]|[0x\da-fA-F]|[0o\d])+$)/', $cur_arg))
            {
                $cur_arg_type = "int";
                $cur_arg_value = substr($cur_arg, strpos($cur_arg, "@") + 1);
                break;
            }
            if(preg_match('/(^nil@nil$)/', $cur_arg))
            {
                $cur_arg_type = "nil";
                $cur_arg_value = "nil"; 
                break;
            }
            if(preg_match('/(^(GF|LF|TF)@[a-zA-Z-_&$*%?!][a-z0-9A-Z-_&$*%!?]*$)/', $cur_arg))
            {
                $cur_arg_type = "var";
                $cur_arg_value = $cur_arg;
                break;
            }
            if(preg_match('/(^string@)/', $cur_arg))
            {
                $cur_arg_type = "string";
                $cur_arg_value = substr($cur_arg, strpos($cur_arg, "@") + 1);
                if(preg_match_all('/(\\\\[0-9]{3})/', $cur_arg) !== substr_count($cur_arg, "\\"))
                {
                    fprintf(STDERR, "Invalid escape sequence in argument of instruction with order: $instruction_order\n");
                    exit(ERR_LEX_SYNTAX_OTHER);
                }
                break;
            }
            fprintf(STDERR, "Error in argument of instruction with order: $instruction_order. Expected type of argument is symb. Check syntax of symb. Program shutting down.\n");
            exit(ERR_LEX_SYNTAX_OTHER);      
    }
    return array($cur_arg_type, $cur_arg_value);
}

//Function calls to helper functions, to check current instruction, before generating it's xml element.
//Function either ends correctly, resulting in xml element of current instruction being created, or fails in one of these helper functions.
function create_instruction_xml($instruction, $arguments)
{
    global $instruction_order;
    global $new_xml_writer;
    global $valid_instructions_list;
    //Check current instruction lexically and syntactically.
    check_instruction_validity($instruction, $arguments);    
    //Instruction xml element generation.
    xmlwriter_start_element($new_xml_writer, 'instruction');
    xmlwriter_start_attribute($new_xml_writer, 'order');
    xmlwriter_text($new_xml_writer, $instruction_order++);
    xmlwriter_end_attribute($new_xml_writer);
    xmlwriter_start_attribute($new_xml_writer, 'opcode');
    xmlwriter_text($new_xml_writer, $instruction);
    xmlwriter_end_attribute($new_xml_writer);
    //Instruction's argument/s xml generation.
    for($curArgNum = 0; $curArgNum < count($arguments); $curArgNum++)
    {
        //Checking current instruction's arguments lexically and syntactically.
        //If argument is correct, generate xml element for it.
        xmlwriter_start_element($new_xml_writer, 'arg'.($curArgNum+1));
        xmlwriter_start_attribute($new_xml_writer, 'type');
        $data = argument_handler($arguments[$curArgNum], $valid_instructions_list[$instruction][$curArgNum]);
        xmlwriter_text($new_xml_writer, $data[0]);
        xmlwriter_end_attribute($new_xml_writer);
        xmlwriter_text($new_xml_writer, $data[1]);
        xmlwriter_end_element($new_xml_writer);
    }
    xmlwriter_end_element($new_xml_writer);
}

while($cur_proccessed_line = fgets(STDIN))
{
    //Removing comment from current line, if it is present.
    $line_no_comment = preg_replace('/(\#.*)/',' ',$cur_proccessed_line);
    //Removing whitespaces from current line.
    $clean_line = trim($line_no_comment);
    //If we find another .IPPcode22 header here, we terminate the program, since this a mistake.
    //Program is terminated with exit code 21.
    if(strtolower($clean_line) === ".ippcode22")
    {
        fprintf(STDERR, "Found another .IPPcode22 header in source code. Program is shutting down.\n");
        exit(ERR_BAD_SOURCE_CODE);
    }
    //Proccessing only non-empty lines, of course.
    if(!empty($clean_line))
    {
        //Current line is split by regular expression into instruction and it's arguments.
        $split_line = array();
        preg_match_all('/(\S+)/', $clean_line, $split_line);
        //Instruction names are case insensitive, converting into upper case, for easier checking.
        $instruction =  strtoupper($split_line[0][0]);
        array_shift($split_line[0]);
        //Sending instruction and arguments into xml generating function.
        create_instruction_xml($instruction, $split_line[0]);
    }
}
//Closing the main xml element program here as well as the document itself.
xmlwriter_end_element($new_xml_writer);
xmlwriter_end_document($new_xml_writer);
//Printing generated xml to stdout.
echo xmlwriter_output_memory($new_xml_writer);
exit(CORRECT_EXECUTION);
?>