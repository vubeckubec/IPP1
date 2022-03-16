Implementation documentation of 1. IPP assignment 2021/2022\
Name and surname: Viktor Kubec\
Login: xkubec03

## Analyzer of source code IPPcode22 (parse.php)
Script parse.php reads source code in IPPcode22 from standard input and performs lexical and syntactical check.
Valid source code is generated into XML represantation. The most important part of my code is the associative array,
which holds all the instructions and their properties. Keys in this array are names of instructions, and values are 
arguments of instructions represented as types of these arguments.

One element of this array is defined as follows:
```php
"CREATEFRAME" => array(),
"SUB" => array("var", "symb", "symb"),
```

## Things needed before analysis itself
First the script checks if given arguments are valid. If not appropriate error messages are displayed and exit codes returned.
After this script checks for the .IPPcode22 header, which must be included in source code. Since there could be any number of
comments and empty lines before this header I had to read them out until I would eventually reach the header(or not). If the 
header was found, program carries on and generates XML header and the main program element. I decided to use XMLWriter for
XML generation. Instructions are generated in function which is called in a while loop. In this loop we first read single line
of source code, remove any comments from it, trim the line and check if there is not another header present in source code.
If so program is terminated and error displayed. Once all this is done, we start proccessing only non empty lines. Line is split
by regular expression and these new tokens are placed into an array. First index of array is saved into new instruction variable.
Rest of the array is shifted and sent to the XML instruction generating function directly.

## The analysis itself
Once the program calls `create_instruction_xml` function, the analysis begins. In this function there is call to `check_instruction_validity`
function, which performs check of instruction itself - checks if the name of instruction is valid and number of arguments is valid.
Than the `create_instruction_xml` function generates XML for the instruction. XML for arguments is generated in for, one argument at a time.
Befor the XML for arguments is generated there is a call to `argument_handler` function. This function checks validity of arguments.
Function takes argument from associative array(argument that should be in source code), and checks wheter current argument is valid according to 
this or not. Everything is checked by regular expressions here, because i found it very easy and effective. Function returns argument type and 
value so that it can be written into XML elements. All of the instruction elements are handled this way.

## Finish
Once the whole source code is checked and generated program just ends the XML document and exits with return code 0 meaning success.
