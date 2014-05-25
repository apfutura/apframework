<?php

// This file is part of apFramework - http://apframework.apfutura.net
//
// apFramework is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// apFramework is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with apFramework.  If not, see <http://www.gnu.org/licenses/>.

/**
 * apFramewrok - A PHP MVC framework to build php sites
 * @package	apFramework
 * @author	<Antoni Rosa Ruiz> toni.rosa@gmail.com
 * @copyright  Copyright (c) 2013, Apfutura Internacional S.L.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$realPath = dirname(__FILE__) . DIRECTORY_SEPARATOR;
DEFINE('_GLOBAL_ROOT_DIR', $realPath, true);
DEFINE('_GLOBAL_SETUP_DIR', $realPath. "ap/config/",true);
DEFINE('_GLOBAL_CONTROLLERS_DIR', $realPath. "controllers/",true);
DEFINE('_GLOBAL_MODEL_DIR', $realPath. "ap/classes/",true);
DEFINE('_GLOBAL_APPMODEL_DIR', $realPath."models/",true);
DEFINE('_GLOBAL_LANG_DIR', $realPath."ap/lang/",true);
DEFINE('_GLOBAL_LIB_DIR', $realPath."ap/lib/",true);
DEFINE('_GLOBAL_TEMPLATES_DIR', $realPath ."templates/",true);
DEFINE('_GLOBAL_JS_DIR', $realPath."js/",true);
DEFINE('_GLOBAL_SQL_DIR', $realPath."ap/sql/",true);
DEFINE('_GLOBAL_TMP_DIR', "/tmp/",true);

require_once(constant('_GLOBAL_SETUP_DIR') . "setup.php" );
require_once(constant('_GLOBAL_MODEL_DIR') . "apApplication.php" );
require_once(constant('_GLOBAL_MODEL_DIR') . "apController.php" );
require_once(constant('_GLOBAL_MODEL_DIR') . "apControllerTask.php" );

//Load
apController::init(constant('_GLOBAL_CONTROLLERS_DIR'), 'index', 'get', 'login');

//Execute
$task = apUtils\getParam($_GET,'task',null);
$operation = apUtils\getParam($_GET,'operation',null);
apController::execute($task, $operation);