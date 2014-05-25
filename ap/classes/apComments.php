<?php
class apComments {

	static public function save_single($code,$commentText) {
		$_db = apDatabase::getDatabaseLink();
		
		if ($_db->getFieldValueEx("comments","code='".$code."'",'id')==null) {			
			$sql = 'INSERT INTO comments (comment,code) VALUES (?,?)';
		} else {
			$sql = 'UPDATE comments SET comment= ? WHERE code=?;';
		}	
		
		if ( $_db->execute($sql,array ($commentText, $code)) ) {
	    	return true;
	    } else {
	    	return FALSE;
	    }
	}
	
	static public function get_single($code) {
		$_db = apDatabase::getDatabaseLink();		
		return $_db->getFieldValueEx("comments","code='".$code."'",'comment');
	}
	
	static public function remove_single($code) {
		$_db = apDatabase::getDatabaseLink();
		
		if ($_db->getFieldValueEx("comments","code='".$code."'",'id')==null) {			
			return true;
		} else {
			$sql = 'DELETE FROM WHERE code=?;';
		}	
		
		if ( $_db->execute($sql,array ($code)) ) {
	    	return true;
	    } else {
	    	return FALSE;
	    }
	}

}