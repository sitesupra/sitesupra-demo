<?php

define('COMBO_FILE_PATH', $_SERVER['DOCUMENT_ROOT']);

//Used to prevent including files other than those in /cms/... folder
define('YUI_BUILD_PATH', COMBO_FILE_PATH . 'cms/');

//Cache folder
define('TEMP_DIR', COMBO_FILE_PATH . '../data/');

define('DS', DIRECTORY_SEPARATOR);