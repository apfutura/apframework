<?php

$realPath = dirname(__FILE__) . DIRECTORY_SEPARATOR;
DEFINE('_GLOBAL_ROOT_DIR', $realPath, true);
DEFINE('_GLOBAL_SETUP_DIR', $realPath. "config/",true);
DEFINE('_GLOBAL_CONTROLLERS_DIR', $realPath. "controllers/",true);
DEFINE('_GLOBAL_APPMODEL_DIR', $realPath."classes/",true);
DEFINE('_GLOBAL_TEMPLATES_DIR', $realPath ."templates/",true);
DEFINE('_GLOBAL_JS_DIR', $realPath."js/",true);
DEFINE('_GLOBAL_SQL_DIR', $realPath."sql/",true);
DEFINE('_GLOBAL_TMP_DIR', "/tmp/",true);

DEFINE('_GLOBAL_MODEL_DIR', $realPath. "ap/classes/",true);
DEFINE('_GLOBAL_LANG_DIR', $realPath."lang/",true);
DEFINE('_GLOBAL_LIB_DIR', $realPath."ap/lib/",true);