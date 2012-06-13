<?php

$sleep_time=rand(1,10);
sleep($sleep_time);
$handle = fopen("temp/".time().'.txt', "w");
