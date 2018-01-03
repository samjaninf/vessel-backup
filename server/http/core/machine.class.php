<?php

require_once 'database.class.php';

class BackupMachine
{
	
	private $_db;
	private $_machine = array();
	private $_exists=false;
	
	public function __construct($hostID) {
		
		$this->_db = BackupDatabase::getDatabase();
		
		if ( gettype($hostID) == 'string') {
			$this->_getMachineByName($hostID);
		}
		else {
			$this->_getMachineByID($hostID);
		}

	}
	
	private function _getMachineByName($hostName) {
		
		$query = "SELECT * FROM backup_machine WHERE name=?";
		if ( $stmt = mysqli_prepare($this->_db->getConnection(), $query) ) {
			
			$stmt->bind_param('s', $hostName);
			
			if ( $stmt->execute() ) {
				
				$result = $stmt->get_result();
				if ( $this->_machine = mysqli_fetch_array($result, MYSQLI_ASSOC) ) {
					$this->_exists=true;
				}
				
			}
		
			$stmt->close();
			
		}
		
	}
	
	private function _getMachineByID($hostID) {
		
		$query = "SELECT * FROM backup_machine WHERE machine_id=?";
		if ( $stmt = mysqli_prepare($this->_db->getConnection(), $query) ) {
			
			$stmt->bind_param('i', $hostID);
			
			if ( $stmt->execute() ) {
				
				$result = $stmt->get_result();
				if ( $this->_machine = mysqli_fetch_array($result, MYSQLI_ASSOC) ) {
					$this->_exists=true;
				}
				
			}
		
			$stmt->close();
			
		}
		
	}
	
	public function exists() {
		return $this->_exists;		
	}
	
	public function getData($field) {
		return $this->_machine[$field];
	}
	
}


?>