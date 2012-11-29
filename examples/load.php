<?php

include dirname(dirname(__FILE__)).'/src/autoload.php';

$factory = new phm\Process\Factory();
print_r($factory->currentInstance());