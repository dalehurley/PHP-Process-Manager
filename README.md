PHP-Process-Manager
===================

PHP Multi-thread Process Manager

This tool was inspired by Matou Havlena of havlena.net who posted a multi thread
process manager on PHP.net.

The tool allows users to load a number of commands to be executed by the OS in
new threads simultaneously. 

## Table of contents

1. [Quick Start](#quick-start)
2. [Acknowledgements](#acknowledgements)
3. [Next Steps](#next-steps)


<a name="quick-start"></a>
## 1. Quick Start

1. Load the process manager and some scripts:

    ```html
include_once('class.process_manager.php');		//load the class file
$manager              = new Processmanager();	//create the manager object
$manager->executable  = "php";					//the Linux executable
$manager->path        = "";						//path to the scripts to run
$manager->show_output = true;					//show the output of the manager
$manager->processes   = 3;						//max concurrent processes
$manager->sleep_time  = 1;						//time between checking if the processes are complete
$manager->addScript("sleep.php", 2);			//add a script and it max execution time in seconds
$manager->addScript("sleep.php", 2);
$manager->addScript("sleep.php", 1);
$manager->addScript("sleep.php", 4);
$manager->addScript("sleep.php", 5);
$manager->addScript("sleep.php");				//no max execution time defaults to 300 seconds
$manager->exec();								//start processing through the code
echo 'Completed all tasks';
    ```

<a name="acknowledgements"></a>
## 2. Acknowledgements
Matou Havlena of havlena.net came up with the original concept and posted it on PHP.NET
Dale Hurley of DaleHurley.com modified the script to make output optional 

<a name="next-steps"></a>
## 3. Next Steps

1. Clean up the Process class