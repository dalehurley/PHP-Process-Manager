<?php

include_once('class.process_manager.php');

$manager              = new Processmanager();
$manager->executable  = "php";
$manager->path        = "";
$manager->show_output = true;
$manager->processes   = 3;
$manager->sleep_time  = 1;
$manager->addScript("sleep.php", 2);
$manager->addScript("sleep.php", 2);
$manager->addScript("sleep.php", 1);
$manager->addScript("sleep.php", 4);
$manager->addScript("sleep.php", 5);
$manager->addScript("sleep.php");
$manager->exec();