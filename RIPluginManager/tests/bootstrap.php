<?php

require(dirname(__FILE__).'/../../../includes/configure.php');
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . DIR_FS_CATALOG);
chdir(DIR_FS_CATALOG); 
require_once('includes/application_top.php');
