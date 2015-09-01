<?php

require_once("Rest.inc.php");

class API extends REST {

    public $data = "";

    const DB_SERVER = "localhost";
    const DB_USER = "root";
    const DB_PASSWORD = "102030";
    const DB = "users";

    private $db = NULL;

    public function __construct() {
        error_log('method: __construct');
        parent::__construct();    // Init parent contructor
        $this->dbConnect();     // Initiate Database connection
    }

    /*
     *  Database connection 
     */

    private function dbConnect() {
        error_log('method: dbConnect');
        // Conecta ao banco de dados
        $this->db = new mysqli(self::DB_SERVER, self::DB_USER, self::DB_PASSWORD, self::DB);

        if (mysqli_connect_errno()) {
            die('N&atilde;o foi poss&iacute;vel conectar-se ao banco de dados: ' . mysqli_connect_error());
            exit();
        }
    }

    public function processApi() {
        error_log('method: processApi');

        if (sizeof($_REQUEST) == 0)
            $this->response('', 404);

        $func = strtolower(trim(str_replace("/", "", $_REQUEST['rquest'])));
        if ((int) method_exists($this, $func) > 0)
            $this->$func();
        else
            $this->response('', 404);    // If the method not exist with in this class, response would be "Page not found".
    }

    private function login() {
        // Cross validation if the request method is POST else it will return "Not Acceptable" status
        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        $email = $this->_request['email'];
        $password = $this->_request['pwd'];

        if (!empty($email) && !empty($password)) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                if ($query = $this->db->query("SELECT user_id, user_fullname, user_email FROM users WHERE user_email = '$email' AND user_password = '" . md5($password) . "' LIMIT 1")) {
                    $result = $query->fetch_array(MYSQLI_ASSOC);
                    // If success everythig is good send header as "OK" and user details
                    $this->response($this->json($result), 200);
                }
            }
        } else {
            $this->response('', 204); // If no records "No Content" status
        }

        // If invalid inputs "Bad Request" status message and reason
        $error = array('status' => "Failed", "msg" => "Invalid Email address or Password");
        $this->response($this->json($error), 400);
    }

    private function insertUser() {
        error_log('method: inserUser');

        if ($this->get_request_method() != 'POST') {
            $this->response('', 406);
        }

        $name = $this->_request['name'];
        $email = $this->_request['email'];
        $password = md5($this->_request['pwd']);
        $status = $this->_request['status'];

        if (!filter_var($name, FILTER_SANITIZE_STRING)) {
            $this->response('', 412);
        } elseif (!filter_var($email, FILTER_SANITIZE_EMAIL)) {
            $this->response('', 412);
        } elseif (!filter_var($status, FILTER_VALIDATE_BOOLEAN)) {
            $this->response('', 412);
        }


        $this->db->query("INSERT INTO users (user_fullname, user_email, user_password, user_status) VALUES ( '{$name}', '{$email}', '{$password}', {$status} ) ");

        if ($this->db->affected_rows > 0) {
            $sucess = array('status' => 'Sucess', 'msg' => 'Successfully one record inserted.');
        } else {
            $sucess = array('status' => 'Erro', 'msg' => 'no record inserted.');
        }

        $this->response($this->json($sucess), 200);
    }

    private function updateUser() {
        error_log('method: updateUser');

        if ($this->get_request_method() != 'PUT') {
            $this->response('', 406);
        }

        $id = $this->_request['id'];
        $email = $this->_request['email'];
        $password = md5($this->_request['pwd']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->response('', 412);
        }

        $this->db->query("UPDATE users SET user_email = '{$email}', user_password = '{$password}' WHERE user_id = {$id} ");

        if ($this->db->affected_rows > 0) {
            $success = array('status' => "Success", "msg" => "Successfully one record updated.");
        } else {
            $success = array('status' => "Erro", "msg" => "no record updated.");
        }
        $this->response($this->json($success), 200);
    }

    private function deleteUser() {
        error_log('method: deleteUser');

        // Cross validation if the request method is DELETE else it will return "Not Acceptable" status
        if ($this->get_request_method() != "DELETE") {
            $this->response('', 406);
        }

        $id = $this->_request['id'];

        if (filter_var($id, FILTER_VALIDATE_INT)) {
            $this->db->query("DELETE FROM users WHERE user_id = {$id} ");
            if ($this->db->affected_rows > 0) {
                $success = array('status' => "Success", "msg" => "Successfully one record deleted.");
            } else {
                $success = array('status' => "Erro", "msg" => "no record deleted.");
            }
            $this->response($this->json($success), 200);
        }
    }

    private function users() {
        error_log('method: users');
        // Cross validation if the request method is GET else it will return "Not Acceptable" status
        if ($this->get_request_method() != "GET") {
            $this->response('', 406);
        }


        if ($query = $this->db->query("SELECT user_id, user_fullname, user_email FROM users WHERE user_status = 1")) {
            $result = array();
            while ($rlt = $query->fetch_array(MYSQLI_ASSOC)) {
                // echo $userRow['user_fullname'] . '<br>';
                $result[] = $rlt;
            }
            // If success everythig is good send header as "OK" and return list of users in JSON format
            $this->response($this->json($result), 200);
        }
        $this->response('', 204); // If no records "No Content" status
    }

    private function user() {
        error_log('method: user');

        if ($this->get_request_method() != "POST") {
            $this->response('', 406);
        }

        $id = $this->_request['id'];

        if( $query = $this->db->query("SELECT user_id as id, user_email as email FROM users WHERE user_id = {$id}")) {
            $result = array();
            $result[] = $query->fetch_array(MYSQLI_ASSOC);
             $this->response($this->json($result), 200);
        }
        
         $this->response('', 204); // If no records "No Content" status        
        
    }

    /*
     * 	Encode array into JSON
     */

    private function json($data) {
        if (is_array($data)) {
            return json_encode($data, JSON_FORCE_OBJECT);
        }
    }

}

$api = new API;
$api->processApi();
