<?php
class apPermissions {
	
	private $message;
	private $pemissionsMatrix;
	protected $_permissionsTable = 'permissions';
	protected $_db;
	
	function __construct() {
  		$this->_db = apDatabase::getDatabaseLink();
		//****** Matrix Permissions *******
		$this->loadPermissions();
  	}
  	
  	public function loadPermissions() {
  		$this->pemissionsMatrix = $this->getPermissionsMatrix();
  	}
  	
	function check($task, $operation, $user)	{

       $message = "Checking Permissions for user '".$user."' and task '".$task . "' / operation '" . $operation. "'.";
       apUtils\logToApache($message);

       // Default will be deny if no explicit permissions are specfied above.
       $result = false;
       $task = strtolower($task);
       if ($operation!=null) $operation = strtolower($operation);
       
       if (!empty($this->pemissionsMatrix[$task]) && !is_null($user)  ) {
               $requiredGroupForTask = null;
               $requiredGroupForOperation = null;

               foreach($this->pemissionsMatrix[$task] as $oper=>$group) {
               		if ($oper=="*") $requiredGroupForTask = $group;
                    if ($oper==$operation) $requiredGroupForOperation = $group;
               }
               
               if (!is_null($requiredGroupForOperation)) {
                       if (in_array($requiredGroupForOperation, $user->getGroups() ) ) { $result = true; }                       
               } else if (!is_null($requiredGroupForTask))  {
                       if (in_array($requiredGroupForTask, $user->getGroups() ) ) { $result = true; }
               } else { $message="No permissions set for task '" .$task . "' " .$operation.""; }
       } else {
               $message="No permissions defined for task '" .$task . "' " .$operation;
       }
		$this->message = $message;
		if (!$result && (strlen($onPermissionsDeniedRedirectTo)>0) ) {
       	    $params="&msg=".$message;
			header('Location: '.$onPermissionsDeniedRedirectTo.$params); 
			exit(0);
		}
		return $result;
	}

	function getLastMessage() {
		return $this->message;
	}
	
	public function getPermissionsMatrix() {
		
		$SQL="SELECT * FROM ".$this->_permissionsTable." ORDER BY task";
		$permissionsList=$this->_db->query($SQL,PDO::FETCH_OBJ);
		
		$currentTask = "";
		foreach ($permissionsList as $p) {
			if ($currentTask!=strtolower($p->task)) {
				$currentTask = strtolower($p->task);
			}
			
			$pdetail[strtolower($p->operation)]=$p->allowed_group;
			if (!empty($permissions[$currentTask])) {
				//echo "--!".$currentTask."/". $p->operation."=".$p->allowed_group."!--";
				//echo "<hr>";
				//html_print_r($permissions);
				
				$permissions[$currentTask] += $pdetail;
				//echo "<hr>";
				//html_print_r($permissions);
				//die();
			} else {
				$permissions[$currentTask] = $pdetail;
				//html_print_r($permissions);
				//echo "<hr style='border:solid 1px red;'>";
			}
			unset($pdetail);
		}
		//echo "<hr>";
		return $permissions;
	}
	
	public function setPermission($task, $operation, $allowed_group ) {		
		
		$task = strtolower($task);
		if ($operation==null) $operation = "*";
		$wherePermission_getPermissionsMatrix  = "task='".$task."' and operation='".$operation."'";
		$old_allowed_group = $this->_db->getFieldValueEx($this->_permissionsTable,$wherePermission_getPermissionsMatrix,"allowed_group");
		
		if ($old_allowed_group==null) {			
			$sql = 'INSERT INTO ' . $this->_permissionsTable. "(task,operation,allowed_group) VALUES ('".$task."','".$operation."',".$allowed_group.");";
		} else {
			$sql = 'UPDATE ' .$this->_permissionsTable. " SET allowed_group = ".$allowed_group." WHERE ".$wherePermission_getPermissionsMatrix.";";
		}	
		//echo $sql;
		return $this->_db->exec($sql);;
	}
	
	public function removePermission($task, $operation ) {
		
		$task = strtolower($task);
		if ($operation==null) {
			$operation = "*";				
		} else {
			$operation = strtolower($operation);
		}
		$wherePermission_getPermissionsMatrix  = "task='".$task."' and operation='".$operation."'";
		$sql = 'DELETE FROM ' . $this->_permissionsTable." WHERE ".$wherePermission_getPermissionsMatrix.";";
		return $this->_db->exec($sql);		
	}

}