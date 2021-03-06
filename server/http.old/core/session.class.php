<?php

require_once 'database.class.php';
require_once 'log.class.php';
require_once 'user.class.php';

class BackupSession
{

	private static $factory;
	private $_userId = '';
	private $_dbconn;
	private $_loginSuccess=false;
	private $_sessionHash;
	private $_sessionId = -1;
	private $_sessionExpired=false;
	private $_ip_address;
	private $_log;

	public function __construct() {

		//Set remote IP
		$this->_ip_address = $_SERVER['REMOTE_ADDR'];

		//Get Database Connection
		$this->_dbconn = BackupDatabase::getDatabase()->getConnection();

		//Get Log Obj
		$this->_log = BackupLog::getLog( $this->_userId );

		//Get or Create User Session
		$this->_initSession();

	}

	function __destruct() {

	}

	public function getIPAddr() {
		return $this->_ip_address;
	}

	public static function getSession()
	{
		if (!self::$factory)
			self::$factory = new BackupSession();

		return self::$factory;
	}

	public function login($username,$pwd) {

		//Get User Password Hash
		if ( $stmt = mysqli_prepare($this->_dbconn, "SELECT LOWER(HEX(user_id)) AS user_id, password FROM backup_user WHERE user_name=? AND active=1 AND activated=1") ) {

			$stmt->bind_param('s', $username);
			$stmt->execute();

			$result = $stmt->get_result();

			if ( $row = $result->fetch_row() ) {

				if ( password_verify($pwd, $row[1]) == TRUE ) {

					//Set UserId
					$this->_userId = $row[0];

					//Update user Id for the current session
					$this->_updateSessionUserId($this->_userId);

					//Password is correct, login successful
					$this->_updateLastLogin();

					$this->_loginSuccess=true;

				}
				else {
					$this->_loginSuccess=false;
					$this->_log->addError("Authentication failed (Username=" . $username . ")", "Authentication");
				}

			}

			$stmt->close();

		}

		return $this->_loginSuccess;

	}

	public function isLoggedIn() {
		return $this->_loginSuccess;
	}

	private function _updateSessionUserId($userId) {

		$query = "UPDATE backup_user_session SET user_id=UNHEX(?) WHERE session_hash=?";

		if ( $stmt = mysqli_prepare($this->_dbconn,$query) ) {

			$stmt->bind_param('ss', $userId, $this->_sessionHash );

			if ( $stmt->execute() ) {
				$this->_log->addMessage("Updated session user Id (" . $userId . ")", "Authentication");
			}

			$stmt->close();

			setcookie('user_id', $userId );

		}

	}

	public function logout() {

		//Clear Cookies
		$this->_clearCookies();

		//Expire Session
		if ( !empty($this->_sessionHash) ) {
			$this->_expireSession();
		}

		session_unset();
		session_destroy();

		$this->_loginSuccess = false;
		$this->_sessionExpired = true;
		$this->_userId = null;

		return true;

	}

	public function getUserId() {
		return $this->_userId;
	}

	private function _updateLastLogin() {

		if ( !$this->_userId )
			return;

		mysqli_query($this->_dbconn, "UPDATE backup_user SET last_login = NOW() WHERE user_id=UNHEX('" . $this->_userId . "')");

	}

	private function _createSessionKey() {
		return bin2hex(random_bytes(32));
	}

	public function getSessionId() {

		$sessionId = '';

		$query = "SELECT session_id FROM backup_user_session WHERE session_hash = '" . $this->_sessionHash . "'";
		if ( $result = mysqli_query($this->_dbconn, $query) ) {
			$sessionId = $result[0];
		}

		return $sessionId;

	}

	private function _validateSession($hash) {

		$isValid=false;

		$query = "SELECT session_id,UNIX_TIMESTAMP(last_accessed) AS last_accessed,LOWER(HEX(user_id)) AS user_id FROM backup_user_session WHERE session_hash=? AND expired=0";
		if ( $stmt = mysqli_prepare($this->_dbconn, $query) ) {

			$stmt->bind_param('s', $hash);
			if ( $stmt->execute() ) {

				if ( $result = $stmt->get_result() ) {

					if ( $row = $result->fetch_assoc() ) {

						$d = new DateTime();
						$d->setTimestamp( $row['last_accessed'] );
						$d->add( new DateInterval('P1D') );

						//Check if session is >= 24 hours old
						if ( time() >= $d->getTimestamp() ) {

							$this->_expireSession($this->_sessionHash);
							$isValid=false;
							$this->_loginSuccess=false; //User is NOT logged in

						}
						else {

							//Session is invalid if it's not expired
							$isValid=true;

							/* If session hash is valid, not expired, and user id is not null, user is logged in */
							if ( !empty($row['user_id']) ) {
								$this->_loginSuccess=true; //User is logged in
							}

						}

					}

				}

			}

			$stmt->close();

		}

		return $isValid;

	}

	private function _expireSession() {

		$query = "UPDATE backup_user_session SET expired=1 WHERE session_hash=?";
		if ( $stmt = mysqli_prepare($this->_dbconn, $query) ) {

			$stmt->bind_param('s', $this->_sessionHash );

			if ( $stmt->execute() ) {

				$this->_sessionExpired=true;
				$this->_sessionHash = '';
				$this->_sessionId = '';

				$this->_log->addMessage("User session has been expired (" . $this->_sessionHash . ")", "Authentication");

			}

			$stmt->close();

		}

	}

	private function _hashSessionKey($key) {
		return hash('sha512', $key );
	}

	private function _initSession() {

		session_start();

		//Check if the user has an existing sessionId
		if ( !empty($_COOKIE['session_key'] ) ) {

			$this->_sessionHash = $this->_hashSessionKey($_COOKIE['session_key']);

			if ( !empty($_COOKIE['user_id'] ) ) {
				$this->_userId = $_COOKIE['user_id'];
				$this->_log->setUserId( $this->_userId );
			}

			//Validate session
			if ( $this->_validateSession($this->_sessionHash) ) {

				//User is already logged in
				//Update last access time
				$this->_updateLastAccessed();
				return;

			}
			else {
				//Clear cookies and force login
				$this->_clearCookies();
				$this->_userId = null;
			}

		}

		//Create new session
		if ( !$this->_userId )
			$this->_userId = ''; //User is not logged in

		$sessionKey = $this->_createSessionKey();
		$this->_sessionHash = $this->_hashSessionKey($sessionKey);
		$ip = $this->getIPAddr();

		$query = "INSERT INTO backup_user_session (user_id,session_hash,ip_address) VALUES(UNHEX(?),?,?)";

		if ( $stmt = mysqli_prepare($this->_dbconn, $query ) ) {

			$stmt->bind_param(
				"sss",
				$this->_userId,
				$this->_sessionHash,
				$ip
			);

			if ( $stmt->execute() ) {

				$this->_sessionId = mysqli_insert_id($this->_dbconn);

				//Set client cookies
				setcookie("session_key", $sessionKey);
				setcookie("user_id", $this->_userId );

			}
			else {
				$this->_log->addError("Failed to insert user session (SessionHash=" . $this->_sessionHash . ")", "Authentication");
			}

			$stmt->close();

		}
		else {
				$this->_log->addError("Failed to insert user session (SessionHash=" . $this->_sessionHash . ")", "Authentication");
		}

	}

	private function _clearCookies() {
		setcookie('session_key', '', time() - 3600);
		setcookie('user_id', '', time() - 3600 );
	}

	private function _updateLastAccessed() {

		@mysqli_query(
			$this->_dbconn,
			"UPDATE backup_user_session SET last_accessed=NOW(),ip_address='" . $this->getIPAddr() ."' WHERE session_hash='" . $this->_sessionHash . "'"
		);

	}

	public function printLoginMsg() {

		echo "Error: You must be authenticated to use this feature. Please <a href=\"user.php?action=login\">login</a> to continue";

	}

	public function getRefererURL() {
		return !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "index.php";
	}

	public function getSessionUserName() {

			if ( !$this->_userId )
				return '';

			$userName = '';

			$query = "SELECT user_name FROM backup_user WHERE user_id=UNHEX(?)";
			if ( $stmt = mysqli_prepare($this->_dbconn, $query) ) {

					$stmt->bind_param('s', $this->_userId);
					if ( $stmt->execute() ) {

						$result = $stmt->get_result();
						$userName = $result[0];

					}

					$stmt->close();

			}

			return $userName;

	}

	public function getSessionUserFullName() {

			if ( !$this->_userId )
				return '';

			$fullName = '';

			$query = "SELECT first_name,last_name FROM backup_user WHERE user_id=UNHEX(?)";
			if ( $stmt = mysqli_prepare($this->_dbconn, $query) ) {

					$stmt->bind_param('s', $this->_userId);
					if ( $stmt->execute() ) {

						$result = $stmt->get_result()->fetch_row();
						$fullName = $result[0] . " " . $result[1];

					}

					$stmt->close();

			}

			return $fullName;

	}

}

?>
