<?php
class apDatabaseTriggers {

	static function processAction($triggeredAction, $triggeredEntity,  $triggeredEntityKey) {					 
		$commandOptions = self::getCommand($triggeredAction, $triggeredEntity,  $triggeredEntityKey);
		
		if ($commandOptions) {
		    $id = $commandOptions['id'];
		    $singleUse = $commandOptions['single_use'];
		    $command = $commandOptions['command'];		    
		    $command = str_replace('{$key}',$triggeredEntityKey,$command);
		    echo "Eval: $command";
		    eval($command);
		    if ($singleUse) { self::delete($id); }
		}
		
		$commandOptions = self::getCommand($triggeredAction, $triggeredEntity,  null);
		if ($commandOptions) {
		    $id = $commandOptions['id'];
		    $singleUse = $commandOptions['single_use'];
		    $command = $commandOptions['command'];		    
		    $command = trim(str_replace('{$key}',$triggeredEntityKey,$command));
		    if (substr($command, -1, 1) != ';' ) $command = $command . ";"; 		    
		   eval($command);
		    if ($singleUse) { self::delete($id); }
		}
		
	}

	static function delete($id) {
	    $command = new apDatabaseTriggerCommand();
	    if ($command->load($id)) {
	        return $command->delete();
	    } else {
	        return false;
	    }	    
	}
	
	static function getCommand($action, $entity, $key) {
	    $_db = apDatabase::getDatabaseLink();
	    
	    if  ( $key== null ) {
	        $keyFilter = 'key IS NULL';
	    } else {
	        $keyFilter = "key='$key'";
	    }
	    $commandOptions = $_db->getFieldsValueEx("ap_triggers","action='$action' AND entity='$entity' AND $keyFilter",array('command' , 'single_use', 'id'));

	    return $commandOptions;
	}
	
	static function add($action, $entity, $key, $command, $singleUse) {
	    $command = new apDatabaseTriggerCommand();
	    return $command->insert( array('action' => $action, 'entity' => $entity, 'key' => $key,  'command' => $command, 'single_use' => $singleUse) );
	}
		
}


Class apDatabaseTriggerCommand extends apBaseElement {

    public function __construct() {
            parent::__construct('ap_triggers');
    }
    
}