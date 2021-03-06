<?php

require_once 'api_session.class.php';
require_once 'api_exception.class.php';
require_once 'file.class.php';
require_once 'user.class.php';
require_once 'upload.class.php';

abstract class API
{
    /**
    * Property: method
    * The HTTP method this request was made in, either GET, POST, PUT or DELETE
    */
    protected $method = '';
    /**
    * Property: endpoint
    * The Model requested in the URI. eg: /files
    */
    protected $endpoint = '';
    /**
    * Property: verb
    * An optional additional descriptor about the endpoint, used for things that can
    * not be handled by the basic methods. eg: /files/process
    */
    protected $verb = '';
    /**
    * Property: args
    * Any additional URI components after the endpoint and verb have been removed, in our
    * case, an integer Id for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
    * or /<endpoint>/<arg0>
    */
    protected $args = array();
    /**
    * Property: rawData
    * Stores raw data from a POST or PUT
    */
    protected $rawData = null;

    /**
    * Property: headers
    * HTTP Request headers
    */
    protected $headers;

    private $_db;
    private $_log;
    private $_session; //API session object

    /**
    * Constructor: __construct
    * Allow for CORS, assemble and pre-process the data
    */
    public function __construct($request)
    {

        //Set output headers
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");

        //Detect endpoint and arguments
        $this->args = explode('/', rtrim($request, '/'));
        $this->endpoint = array_shift($this->args);
        if (array_key_exists(0, $this->args) && !is_numeric($this->args[0])) {
            $this->verb = array_shift($this->args);
        }

        //Set HTTP Method
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } elseif ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new APIException("Unexpected Header");
            }
        }

        //Get Headers
        $this->_parseHeaders();

        //Build objects
        $this->_db = BackupDatabase::getDatabase();
        //
        $this->_session = BackupAPISession::getSession();
        $this->_session->logSession($this->endpoint, $this->method);
        //
        $this->_log = BackupLog::getLog($this->_session->getUserId());

        switch ($this->method) {
          case 'DELETE':
          case 'POST':
            $this->request = $this->_cleanInputs($_POST);
            $this->rawData = file_get_contents("php://input");
            break;
          case 'GET':
            $this->request = $this->_cleanInputs($_GET);
            break;
          case 'PUT':
            $this->request = $this->_cleanInputs($_GET);
            $this->rawData = file_get_contents("php://input");

            //Handle multipart/form-data PUT request
            if (strpos($headers['Content-Type'], 'multipart/form-data') >= 0) {
                $this->_parseMultipartPut();
            }

            break;
          default:
            throw new APIException('Invalid Method', 405);
            break;
        }

    }

    public function executeAPI()
    {
        if (method_exists($this, $this->endpoint)) {
            return $this->{$this->endpoint}($this->args);
        }

        throw new APIException("No Endpoint: $this->endpoint", 404);
    }

    private function _response($data, $status=200)
    {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        return json_encode(array( 'response' => $data ), JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);
    }

    private function _error($data, $status=400)
    {

        //Log Error
        $this->_log->addError($data, "API", 1);

        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));

        return json_encode(array( 'error' => array('message' => $data, 'code' => $status) ), JSON_FORCE_OBJECT | JSON_PRETTY_PRINT);
    }

    private function _authError($data, $status=401)
    {

        //Log Error
        $this->_log->addError($data, "API", 1);

        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));

        return json_encode(
          array( 'error' =>
            array('message' => $data,
            'token_expired' => $this->_session->isTokenExpired(),
            'authenticated' => $this->_session->isAuthenticated()
            )
          ),
          JSON_FORCE_OBJECT | JSON_PRETTY_PRINT
        );
    }

    private function _cleanInputs($data)
    {
        $clean_input = array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
        }
        return $clean_input;
    }

    private function _requestStatus($code)
    {
        $status = array(
          200 => 'OK',
          400 => 'Bad Request',
          401 => 'Not Authorized',
          403 => 'Forbidden',
          404 => 'Not Found',
          405 => 'Method Not Allowed',
          500 => 'Internal Server Error',
        );

        return ($status[$code]) ? $status[$code] : $status[500];
    }

    private function _parseHeaders()
    {
        $this->headers = apache_request_headers();
    }

    public function parseMultipartPut()
    {
        global $_PUT;

        //Split the data into two parts
        $parts = explode("\r\n\r\n", $this->rawData, 2);

        //Make sure we have two parts
        if (count($parts) <= 1) {
            return;
        }

        //Find the boundary
        $pattern = '/(Content-Type: multipart\/form-data; boundary=)(.+)/'; //\/form-data; boundary=)(.+)$
        preg_match($pattern, $parts[0], $matches);

        //$matches[2] should contain the boundary
        if (!isset($matches[2])) {
            return;
        }

        $boundary = "--" . trim($matches[2]);

        $multiparts = array_filter(explode($boundary, trim($parts[1])));

        //Iterate through parts and create global keys for disposition names
        foreach ($multiparts as $key => $value) {
            $arr = explode("\r\n\r\n", $value);

            if (count($arr) <= 1) {
                continue;
            }

            //Parse the PUT variable name
            $pattern = '/(name=")(.+)(")/';
            preg_match($pattern, $value, $matches);

            if (!isset($matches[2])) {
                continue;
            }

            $GLOBALS['PUT'][$matches[2]] = trim($arr[1]);
        }
    }

    private function test()
    {
        echo $this->_response("Hello World");
    }

    private function activate()
    {
        if ($this->method != "POST") {
            throw new APIException("This HTTP method is not supported", 405);
        }

        //Verify that the activation code is correct
        $request = json_decode($this->rawData, true);

        if (empty($request['activation_code'])) {
            throw new APIException("Activation code is missing", 400);
        }

        if (empty($request['user_name'])) {
            throw new APIException("Username is missing", 400);
        }

        $userName = trim($request['user_name']);
        $activationCode = trim($request['activation_code']);

        $user = new BackupUser($userName);
        $isActivated = $user->getValue("activated") == 1 ? true : false;

        if ($accessToken = $user->activateUser($activationCode)) {
            $isActivated=true;
        }

        //Generate a refresh token?
        $refreshToken = $isActivated ? $user->addRefreshToken() : "";

        $msg = '';
        if (!$accessToken && !$isActivated) {
            $msg = "Error: User could not be activated";
        } elseif (!$accessToken && $isActivated) {
            $msg = "User is already activated";
        } else {
            $msg = "User has been successfully activated";
        }

        echo $this->_response(array(
          "user_name" => $userName,
          "user_id" => $user->getUserId(),
          "access_token" => $accessToken,
          "refresh_token" => $refreshToken,
          "is_activated" => $isActivated,
          "activation_code" => $activationCode,
          "message" => $msg
        ));

    }

    /**
    * Refreshes an access token
    */
    private function refresh_token()
    {
        if ($this->method != "POST") {
            throw new APIException("This HTTP method is not supported", 405);
        }

        //Parse the JSON request
        $request = json_decode($this->rawData, true);

        //User ID and Refresh token are Required
        $requiredFields = array(
          "user_id",
          "refresh_token"
        );

        foreach ($requiredFields as $key => $val) {
            if (!isset($request[$val])) {
                throw new APIException($val . " is missing from JSON payload", 400);
            }
        }

        $user = new BackupUser($request['user_id']);

        if (!$user->exists()) {
            throw new APIException("Invalid User Id was provided", 400);
        }

        //Generate a new access token
        $accessToken = $user->refreshAccessToken($request['refresh_token']);

        if (empty($accessToken)) {
            throw new APIException("The provided refresh token was invalid", 400);
        }

        //Generate a new refresh token
        $refreshToken = $user->addRefreshToken();

        //Send new access and refresh token back to the client
        echo $this->_response(array(
          "access_token" => $accessToken,
          "refresh_token" => $refreshToken,
          "token_expiry" => $user->getTokenExpiry()
        ));

    }

    /**
    * Returns client settings to the client
    */
    private function settings()
    {
        if ($this->method != "GET") {
            throw new APIException("This method is not supported", 405);
        }

        if (!$this->_session->isAuthenticated()) {
            echo $this->_authError("You are not authorized to perform this action", 401);
            return;
        }

        $settings = array();

        $query = "SELECT a.setting_id,a.name,a.value,a.data_type,b.setting_id,b.value AS override_value FROM backup_client_setting AS a LEFT JOIN backup_user_setting AS b ON a.setting_id = b.setting_id AND b.user_id=UNHEX(?)";
        if ( $stmt = mysqli_prepare($this->_db->getConnection(), $query) ) {

          $userId = $this->_session->getUserId();

          $stmt->bind_param('s', $userId);

          if ( $stmt->execute() ) {

            $result = $stmt->get_result();

            while ($row = mysqli_fetch_array($result)) {
                $val = !$row['override_value'] ? $row['value'] : $row['override_value'];

                $settings[$row['name']] = array();
                $settings[$row['name']]['value'] = $row['data_type'] == "int" ? (int)$val : (string)$val;
                $settings[$row['name']]['type'] = $row['data_type'];
                $settings[$row['name']]['user_override'] = !$row['override_value'] ? false : true;
            }

          }

          $stmt->close();

        }

        echo $this->_response(array( "settings" => $settings ), 200);
    }

    private function user()
    {
    }

    private function file()
    {
        if (!$this->_session->isAuthenticated()) {
            echo $this->_authError("You are not authorized to perform this action", 401);
            return;
        }

        if ($this->method == "GET") {
            if (empty($this->args[0])) {
                throw new APIException("File Id is missing or does not exist", 400);
            }

            $file = new BackupFile($this->args[0], $this->_session->getUserId());

            if (!$file->exists()) {
                throw new APIException("File does not exist", 400);
            }

            echo $this->_response(array( 'file' => $file->getObject() ));

            return;
        }

        if ($this->method == "POST") {

            $uploadAction = isset($_GET['action']) ? $_GET['action'] : "";

            /**
            ** Upload a single file, or a part of a file
            **/
            if (empty($uploadAction) || $uploadAction == "upload") {
                if (!isset($_POST['metadata'])) {
                    throw new APIException("File metadata is missing", 400);
                }

                if (!isset($_POST['fileContent'])) {
                    throw new APIException("File content is missing", 400);
                }

                $metadata = json_decode($_POST['metadata']);

                if (json_last_error() != JSON_ERROR_NONE) {
                    throw new APIException("JSON payload is invalid", 400);
                }

                $upload = new BackupUpload($metadata);

                if (!$upload->uploadPart($_POST['fileContent'])) {
                    throw new APIException("Failed to upload file content: " . $upload->getError(), 400);
                }

                echo $this->_response(array("upload_id" => $upload->getUploadId(), "message" => "File was uploaded successfully"));
                return;
            }

            /**
            ** Initialize new file upload
            **/
            if ($uploadAction == "init") {
                $metadata = json_decode($this->rawData);

                if (json_last_error() != JSON_ERROR_NONE) {
                    throw new APIException("JSON payload is invalid", 400);
                }

                $upload = new BackupUpload($metadata);
                if (!$uploadId = $upload->initUpload()) {
                    throw new APIException("Failed to initialize upload: " . $upload->getError(), 400);
                }

                echo $this->_response(array("upload_id" => $uploadId));
                return;
            }
        }
    }

    private function heartbeat()
    {
        if (!$this->_session->isAuthenticated()) {
            echo $this->_authError("You are not authorized to perform this action", 401);
            return;
        }

        if ($this->method != "POST") {
            throw new APIException("Invalid request method", 400);
        }

        $payload = json_decode($this->rawData, true);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new APIException("JSON payload is invalid", 400);
        }

        $requiredFields = array(
          'host_name',
          'os',
          'client_version',
          'domain'
        );

        foreach ($requiredFields as $key => $val) {
            if (!isset($payload[$val])) {
                throw new APIException($val . " is missing from JSON payload", 400);
                return;
            }
        }

        $ts = time();
        $ip = $_SERVER['REMOTE_ADDR'];

        //Create or Update Client Machine
        $machineId = -1;
        $query = "INSERT INTO backup_machine (name,os,dns_name,ip_address,domain,client_version,last_check_in) VALUES(?,?,?,?,?,?,FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE machine_id=LAST_INSERT_Id(machine_id),ip_address=?,domain=?,client_version=?";
        if ($stmt = mysqli_prepare($this->_db->getConnection(), $query)) {
            $stmt->bind_param(
              'ssssssisss',
              $payload['host_name'],
              $payload['os'],
              $payload['host_name'],
              $ip,
              $payload['domain'],
              $payload['client_version'],
              $ts,
              $ip,
              $payload['domain'],
              $payload['client_version']
            );

            if ($stmt->execute()) {
                $machineId = mysqli_insert_id($this->_db->getConnection());
            }

            $stmt->close();
        }

        $userId = $this->_session->getUserId();

        //Add or update user => machine association
        $query = "INSERT INTO backup_user_machine (machine_id,user_id,last_check_in) VALUES (?,UNHEX(?),FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE last_check_in=FROM_UNIXTIME(?)";
        if ($stmt = mysqli_prepare($this->_db->getConnection(), $query)) {
            $stmt->bind_param('isii', $machineId, $userId, $ts, $ts);
            $stmt->execute();
            $stmt->close();
        }

        echo $this->_response(array("machine_id" => $machineId, "last_check_in" => $ts ));
    }
}
