<?php

/*
 ____                                                      __  __                            
/\  _`\        __                                         /\ \/\ \                           
\ \ \L\ \_ __ /\_\    ___ ___      __     _ __   __  __   \ \ \/'/'     __   __  __    ____  
 \ \ ,__/\`'__\/\ \ /' __` __`\  /'__`\  /\`'__\/\ \/\ \   \ \ , <    /'__`\/\ \/\ \  /',__\ 
  \ \ \/\ \ \/ \ \ \/\ \/\ \/\ \/\ \L\.\_\ \ \/ \ \ \_\ \   \ \ \\`\ /\  __/\ \ \_\ \/\__, `\
   \ \_\ \ \_\  \ \_\ \_\ \_\ \_\ \__/.\_\\ \_\  \/`____ \   \ \_\ \_\ \____\\/`____ \/\____/
    \/_/  \/_/   \/_/\/_/\/_/\/_/\/__/\/_/ \/_/   `/___/> \   \/_/\/_/\/____/ `/___/> \/___/ 
                                                     /\___/                      /\___/      
                                                     \/__/                       \/__/       
*/

/* HTTP Status Codes
200 OK
201 Created
204 No Content (Request was successful but no content to return)
400 Bad Request
401 Unauthorized
403 Forbidden
404 Not Found
405 Method Not Allowed
409 Conflict 
422 Unprocessable Entity
500 Internal Server Error
501 Not Implemented
503 Service Unavailable

*/

function loadEnv($path)
//Function that loads the environment variables from the .env file
{
    if (!file_exists($path))
        return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}
loadEnv(__DIR__ . '/.env');

//Sets the headers for the API
header("Access-Control-Allow-Origin: *"); //allow all cors
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");

class Database {
        private static $instance = null;
        private $conn;

        private function __construct() {
            //Constructor using the environment variables
            $this->conn = new mysqli(
                getenv('DB_HOST'),
                getenv('DB_USER'),
                getenv('DB_PASSWORD'),
                getenv('DB_NAME'),
                getenv('DB_PORT') ?: 3306
            );

            if ($this->conn->connect_error) {
                throw new Exception("Database connection failed: " . $this->conn->connect_error);
            }
        }

        public static function getInstance() {
            //If we are not connected to the database, create a new instance
            if (!self::$instance) {
                self::$instance = new Database();
            }
            return self::$instance;
        }

        public function getConnection() {
            //Returns the connection to the database
            return $this->conn;
        }

        public function __destruct() {
            //Closes the connection to the database
            if ($this->conn) {
                $this->conn->close();
            }
        }

        private function __clone() {}
        public function __wakeup() {}
}//Database class

class ResponseAPI {
    public static function send($message, $data = null, $code = 200) {
        //Sends a JSON response to the client.
        http_response_code($code); //Set the HTTP response code

        header('Content-Type: application/json');
        $response = [
            'status' => $code < 400 ? 'success' : 'error',
            'timestamp' => time() * 1000,
            'code' => $code
        ];
        if ($message !== null) {
            $response['message'] = $message;
        }
        if ($data !== null && $data !== []) {
            $response['data'] = $data;
        }
        echo json_encode($response);

        exit;
    }

    public static function error($message, $data = null, $httpCode = 400) {
        self::send($message, $data, $httpCode);
    }
}//ResponseAPI class

class Authorise {
    public static function authenticate($apikey, $requiredUserType){
        //Checks if the API key is valid, and if it is it checks if the user type is valid (able to do the required action)
        if (empty($apikey)) {
            ResponseAPI::error('API key is required to authenticate user', 401);
        }
        if (empty($requiredUserType)) {
            ResponseAPI::error('User type is required to authenticate user', 401);
        }
        if ($requiredUserType !== 'Customer' && $requiredUserType !== 'Admin') {
            ResponseAPI::error('Invalid User type to check if user is authorized', 400);
        }

        //Validate the API key
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('SELECT * FROM User WHERE apikey=?');
        $stmt->bind_param('s', $apikey);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows != 1) {
            ResponseAPI::error('Invalid API key or User not found', 401);
        }

        //Check if the user type is valid
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['user_type'] !== $requiredUserType) {
                ResponseAPI::error('User type not allowed to perform this action', 403);
            }
        }
    }//authenticate
}//Authorise class

class Tester {
    public static function handleTest($requestData) {
        // Return the request data directly as the data field and a success message
        ResponseAPI::send("Test Successful!", $requestData, 200);
    }
}//Tester class

class USER {
    // Validation rules for each field
    private static $validationRules = [
        'name' => [
            'required' => true,
            'pattern' => '/^[a-zA-Z]{1,50}$/',
            'message' => 'Name must be only letters and max 50 characters'
        ],
        'surname' => [
            'required' => true,
            'pattern' => '/^[a-zA-Z]{1,50}$/',
            'message' => 'Surname must be only letters and max 50 characters'
        ],
        'phone_number' => [
            'required' => false,
            'pattern' => '/^\d{10}$/',
            'message' => 'Phone number must be exactly 10 digits'
        ],
        'email' => [
            'required' => true,
            'pattern' => FILTER_VALIDATE_EMAIL,
            'message' => 'Email must be valid and max 100 characters',
            'max_length' => 100
        ],
        'password' => [
            'required' => true,
            'pattern' => "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/",
            'message' => 'Password must be at least 8 characters with upper/lower case, number, and special character'
        ],
        'street_number' => [
            'required' => false,
            'max_length' => 10,
            'message' => 'Street number must be 10 characters or less'
        ],
        'street_name' => [
            'required' => false,
            'pattern' => '/^[a-zA-Z\s]+$/',
            'max_length' => 100,
            'message' => 'Street name must be only letters and spaces, max 100 characters'
        ],
        'suburb' => [
            'required' => false,
            'pattern' => '/^[a-zA-Z\s]+$/',
            'max_length' => 100,
            'message' => 'Suburb must be only letters and spaces, max 100 characters'
        ],
        'city' => [
            'required' => false,
            'pattern' => '/^[a-zA-Z\s]+$/',
            'max_length' => 100,
            'message' => 'City must be only letters and spaces, max 100 characters'
        ],
        'zip_code' => [
            'required' => false,
            'max_length' => 5,
            'message' => 'Zip code must be 5 characters or less'
        ]
    ];

    private static function validateField($field, $value, $ignoreRequired = false) {
        if (!isset(self::$validationRules[$field])) {
            return true;
        }

        $rule = self::$validationRules[$field];
        $value = trim($value);

        // If the field is required and empty (and not ignoring required), return required error
        if (!$ignoreRequired && $rule['required'] && $value === '') {
            return $rule['message'];
        }

        // If the field is not required and empty, skip further validation
        if ($value === '' && !$rule['required'] && !$ignoreRequired) {
            return true;
        }

        // Check max length if specified
        if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
            return $rule['message'];
        }

        // Special handling for email validation
        if ($field === 'email' && !filter_var($value, $rule['pattern'])) {
            return $rule['message'];
        }

        // REGEX validation for other fields
        if (isset($rule['pattern']) && $field !== 'email' && !preg_match($rule['pattern'], $value)) {
            return $rule['message'];
        }

        return true;
    }

    public static function register($requestData) {
        $errors = [];
        $validFields = [];

        // Validate all provided fields
        foreach ($requestData as $field => $value) {
        if (isset(self::$validationRules[$field])) {
            $validationResult = self::validateField($field, $value);
            if ($validationResult !== true) {
                $errors[$field] = $validationResult;
            } else {
                $validFields[$field] = trim($value);
            }
        }
    }

        // Check required fields are present
        foreach (self::$validationRules as $field => $rule) {
        if ($rule['required'] && !array_key_exists($field, $requestData)) {
            $errors[$field] = "Error: The $field field is required.";
        }
    }

        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422); //422 Unprocessable Entity
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM User WHERE email = ?");
        $stmt->bind_param("s", $validFields['email']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            ResponseAPI::error("Email already exists: Please use a different email or log into your account",null, 409); //409 Conflict
        }
        $stmt->close();

        // Generate apikey and salt and hash password
        $salt = bin2hex(random_bytes(16));
        $passwordWithSalt = $validFields['password'] . $salt;
        $hashedPassword = password_hash($passwordWithSalt, PASSWORD_DEFAULT);
        $apikey = bin2hex(random_bytes(32));

        $validFields['apikey'] = $apikey;
        $validFields['salt'] = $salt;
        $validFields['password'] = $hashedPassword;
        $validFields['user_type'] = 'Customer';

        // Build dynamic SQL query
        $userFields = ['name', 'surname', 'email', 'password', 'apikey', 'salt', 'user_type'];
        $optionalFields = ['phone_number', 'street_number', 'street_name', 'suburb', 'city', 'zip_code'];

        foreach ($optionalFields as $field) {
            if (isset($validFields[$field])) {
                $userFields[] = $field;
            }
        }

        $validFields['user_type'] = 'Customer';

        // Prepare placeholders and types
        $placeholders = str_repeat('?,', count($userFields) - 1) . '?';
        $types = str_repeat('s', count($userFields));
        
        // Build column list
        $columns = implode(', ', $userFields);
        
        // Build values array in correct order
        $values = [];
        foreach ($userFields as $field) {
            $values[] = $validFields[$field];
        }

        // Insert user into User table
        $stmt = $conn->prepare("INSERT INTO User ($columns) VALUES ($placeholders)");
        $stmt->bind_param($types, ...$values);

        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("User registration failed: " . $conn->error, ['database_error' => $conn->error], 500); //500 Internal Server Error
        }

        $user_id = $conn->insert_id;
        $stmt->close();

        //Insert into CUSTOMER table
        $stmt = $conn->prepare("INSERT INTO Customer (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Customer registration failed: " . $conn->error, ['database_error' => $conn->error], 500); //500 Internal Server Error
        }
        $stmt->close();

        ResponseAPI::send("User registered successfully", [
            'user_id' => $user_id,
            'apikey' => $apikey
        ], 201); //201 Created
    }//register


}//USER class

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);

    if (empty($requestData) || !is_array($requestData)) {
        ResponseAPI::error("Invalid request data", null, 400);
    }

    if (!isset($requestData['type'])) {
        ResponseAPI::error("Request type is required", null, 400);
    }

    try {
        $db = Database::getInstance();
        
        switch ($requestData['type']) {
            case 'Test':
                Tester::handleTest($requestData);
                break;
                
            case 'Register':
                USER::register($requestData);
                break;
                
                
            default:
                ResponseAPI::error("Invalid request type", null, 400);
        }
    } catch (Exception $e) {
        ResponseAPI::error("An unexpected error occurred: " . $e->getMessage(), ['error' => $e->getMessage()], 500);
    }
} else {
    ResponseAPI::error("Method not allowed", null, 405);
}

//UP TO HERE WORKS IS UPDATED



// class API
// {
//     // Access the environment variables

//     private $dbHost;
//     private $dbName;
//     private $dbUser;
//     private $dbPassword;
//     private $dbPort;

//     private $conn;

//     public function __construct()
//     {
//         $this->dbHost = getenv('DB_HOST');
//         $this->dbName = getenv('DB_NAME');
//         $this->dbUser = getenv('DB_USER');
//         $this->dbPassword = getenv('DB_PASSWORD');
//         $this->dbPort = getenv('DB_PORT') ?: 3306;  // default to 3306 if not set
//     }

//     //methods////////
//     // Method to send JSON response
//     public function sendResponse($status, $message, $data = [])
//     {
//         header('Content-Type: application/json');
//         $response = [
//             'status' => $status,
//             'timestamp' => time() * 1000 // Convert to milliseconds
//         ];
//         if (!empty($message)) {
//             $response['message'] = $message;
//         } elseif (!empty($data)) {
//             $response['data'] = $data;
//         }
//         echo json_encode($response);
//         exit;
//     }

//     public function TestResponse($requestData)
//     {
//         try {
//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, (int) $this->dbPort);

//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//             $hello = isset($requestData['hello']) ? trim($requestData['hello']) : '';
//             $world = isset($requestData['world']) ? trim($requestData['world']) : '';

//             if (empty($hello) || empty($world)) {
//                 $this->sendResponse('error', "missing required fields");
//             }

//             file_put_contents("debug.json", json_encode($requestData, JSON_PRETTY_PRINT));



//             $this->sendResponse("Success", "Hello world back to you!");
//             $conn->close();
//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }

//     }

//     public function Register($requestData)
//     {

//         try {
//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);

//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//             $name = isset($requestData["name"]) ? trim($requestData["name"]) : "";
//             $surname = isset($requestData["surname"]) ? trim($requestData["surname"]) : "";
//             $phone_number = isset($requestData["phone_number"]) ? trim($requestData["phone_number"]) : "";
//             $email = isset($requestData["email"]) ? trim($requestData["email"]) : "";
//             $password = isset($requestData["password"]) ? trim($requestData["password"]) : "";
//             $street_number = isset($requestData["street_number"]) ? trim($requestData["street_number"]) : "";
//             $street_name = isset($requestData["street_name"]) ? trim($requestData["street_name"]) : "";
//             $suburb = isset($requestData["suburb"]) ? trim($requestData["suburb"]) : "";
//             $city = isset($requestData["city"]) ? trim($requestData["city"]) : "";
//             $zip_code = isset($requestData["zip_code"]) ? trim($requestData["zip_code"]) : "";
//             $user_type = isset($requestData["user_type"]) ? trim($requestData["user_type"]) : "";



//             //must have unique email
//             if (empty($name) || empty($surname) || empty($phone_number) || empty($email) || empty($password)) {
//                 $this->sendResponse('error', 'Missing required fields');
//             } elseif (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/', $email)) {
//                 $this->sendResponse('emailError', 'Invalid email address');
//             } elseif (!preg_match("/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/", $password)) {
//                 $this->sendResponse('passwordError', 'Password does not meet requirements');
//             } else {
//                 //check if email is unique
//                 $emailcheck = $conn->prepare("SELECT * FROM User WHERE email = ?");
//                 $emailcheck->bind_param("s", $email);
//                 $emailcheck->execute();

//                 if ($emailcheck->get_result()->fetch_assoc()) {
//                     $this->sendResponse("error", "Email already exists");
//                 }

//                 //All good so we can insert the user
//                 //set api key
//                 $apikey = base64_encode(random_bytes(32));
//                 $password = password_hash($password, PASSWORD_DEFAULT);
//                 $sqlInsert = $conn->prepare("INSERT INTO User(name,surname, phone_number,apikey, email, password, street_number, street_name, suburb,city,zip_code, user_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
//                 $sqlInsert->bind_param("ssssssssssss", $name, $surname, $phone_number, $apikey, $email, $password, $street_number, $street_name, $suburb, $city, $zip_code, $user_type);

//                 if ($sqlInsert->execute()) {
//                     $user_id = mysqli_insert_id($conn);
//                     if ($user_type === "Customer") {
//                         $pfp = isset($requestData["profile_picture"]) ? trim($requestData["profile_picture"]) : "";
//                         $insertC = $conn->prepare("INSERT INTO Customer(user_id, profile_picture) VALUES (?,?)");
//                         $insertC->bind_param("is", $user_id, $pfp);
//                         $insertC->execute();
//                         $insertC->close();
//                     } else if ($user_type === "Admin") {
//                         $salary = isset($requestData["salary"]) ? trim($requestData["salary"]) : "";
//                         $position = isset($requestData["position"]) ? trim($requestData["position"]) : "";
//                         $insertA = $conn->prepare("INSERT INTO Admin(user_id, salary, position) VALUES (?,?,?)");
//                         $insertA->bind_param("ids", $user_id, $salary, $position);
//                         $insertA->execute();
//                         $insertA->close();
//                     } else {
//                         $this->sendResponse("error", "user type unrecognized");
//                         return;
//                     }
//                 } else {
//                     $this->sendResponse("error", "User registration failed");
//                 }

//                 $sqlInsert->close();
//                 $conn->close();


//             }

//             $this->sendResponse("success", "Inserted new user");



//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             $this->sendResponse("error", $e->getMessage());
//             exit;
//         }

//     }

//     public function Login($requestData)
//     {
//         try {
//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);

//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//             $email = isset($requestData["email"]) ? trim($requestData["email"]) : "";
//             $password = isset($requestData["password"]) ? trim($requestData["password"]) : "";

//             if (empty($email) || empty($password)) {
//                 $this->sendResponse("error", "All fields must be valid");
//             }


//             $stmt = $conn->prepare("SELECT * FROM User WHERE email=?");
//             $stmt->bind_param("s", $email);
//             $stmt->execute();

//             $result = $stmt->get_result()->fetch_assoc();
//             //echo("Email: ".$result["email"] );
//             //echo("Password: ".$result["password"] );
//             if (password_verify($password, $result["password"])) {
//                 $cookie_email = $result["email"];
//                 $cookie_name = $result["name"]; //To use when displaying the users profile
//                 $cookie_surname = $result["surname"];
//                 $cookie_key = $result["apikey"];

//                 setcookie("userapikey", $cookie_key, time() + (259200 * 30), "/"); //set for 3 days
//                 setcookie("useremail", $cookie_email, time() + (259200 * 30), "/"); //set for 3 days
//                 setcookie("username", $cookie_name, time() + (259200 * 30), "/"); //set for 3 days
//                 setcookie("usersurname", $cookie_surname, time() + (259200 * 30), "/"); //set for 3 days


//                 $this->sendResponse(
//                     "success"
//                     ,
//                     [
//                         'apikey' => $result["apikey"]
//                         ,
//                         'username' => $result["name"]
//                         ,
//                         'surname' => $result["surname"]
//                         ,
//                         'email' => $result["email"]
//                     ]
//                 );
//             } else {
//                 $this->sendResponse("error", "Unknown email or password");
//             }


//             $stmt->close();
//             $conn->close();

//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }





//     }
//     public function ViewAllProducts($requestData)
//     {

//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//             $stmt = $conn->prepare("SELECT * FROM Product");
//             $stmt->execute();
//             $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
//             //$data = $result->fetch_all(MYSQLI_ASSOC);
//             if (!empty($result)) {
//                 $this->sendResponse("success", $result);
//             }
//             $stmt->close();
//             $conn->close();
//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }

//     }
//     public function RateProduct($requestData)
//     {

//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//             $productid = isset($requestData["product_id"]) ? $requestData["product_id"] :"";
//             $score = isset($requestData["score"]) ? $requestData["score"] : 5;
//             $description = isset($requestData["description"]) ? $requestData["description"] :"";
//             $userid = isset($requestData["user_id"]) ? $requestData["user_id"] :"";

//             $sqlInsert = $conn->prepare("INSERT INTO Rating(score, description, user_id, product_id) VALUES (?,?,?,?)");
//             $sqlInsert->bind_param("isii", $score, $description,$userid, $productid);
//             $sqlInsert->execute();
//             $result = $sqlInsert->get_result();
//             if(!empty($result)){
//                 $this->sendResponse("success", "Inserted rating");
//             }

//             $conn->close();
//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }
//     }
//     public function AddProduct($requestData)
//     {
//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }
//     }
//     public function UpdateProduct($requestData)
//     {

//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }
//     }
//     public function DeleteProduct($requestData)
//     {

//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }
//     }
//     public function ViewRatings($requestData)
//     {
//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }

//             $productid = isset($requestData["product_id"]) ? $requestData["product_id"] : 0;
//             $stmt = $conn->prepare("SELECT * FROM Rating WHERE product_id=?");
//             $stmt->bind_param("i", $productid);
//             $stmt->execute();
//             $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
//             if(!empty($result)) {
//                 $this->sendResponse("success", $result);
//             }
//             $stmt->close();
//             $conn->close();
//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }
//     }
//     public function FilterProducts($requestData)
//     {
//         //filter based on whatever
//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }
//     }

//     public function UpdateAdmin($requestData)
//     {
//         //filter based on whatever
//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }

//             $fields = [];
//             $adminFields =[];
//             $values = [];
//             $adminValues = [];
//             $types = "";
//             $adminTypes = "";
//             $fieldMap = [
//                 "name" => "s",
//                 "surname" => "s",
//                 "phone_number" => "s",
//                 "apikey" => "s",
//                 "email" => "s",
//                 "password" => "s",
//                 "street_number" => "s",
//                 "street_name" => "s",
//                 "suburb" => "s",
//                 "city" => "s",
//                 "zip_code" => "s",
//                 "user_type" => "s"
//             ];
//             $adminFieldMap = [

//                 "salary" => "d",
//                 "position" => "s"
//             ];

//             foreach ($fieldMap as $key => $type) {
//                 if (isset($requestData[$key])) {
//                     $fields[] = "$key = ?";
//                     $values[] = $requestData[$key];
//                     $types .= $type;
//                 }
//             }

//             foreach ($adminFieldMap as $key => $type) {
//                 if (isset($requestData[$key])) {
//                     $adminFields[] = "$key = ?";
//                     $adminValues[] = $requestData[$key];
//                     $adminTypes .= $type;
//                 }
//             }

//             if(count($fields) > 0 && isset($fieldMap['id']))
//             {

//             $conn->begin_transaction();

//             // Update User table
//             $userSql = "UPDATE User SET ". implode(", ", $fields) . "WHERE id=?";
//             $userStmt = $conn->prepare($userSql);
//             $userStmt->bind_param($types, $values);
//             $userStmt->execute();

//             // Update Admin table
//             $adminSql = "UPDATE Admin SET " . implode(", ", $adminFields) ." WHERE user_id=?";
//             $adminStmt = $conn->prepare($adminSql);
//             $adminStmt->bind_param($adminTypes, $adminValues, $fieldMap['id']); // d for double (salary), s for string, i for int
//             $adminStmt->execute();

//             $conn->commit();

//             $conn->close();

//             }
//         } catch (Exception $e) {
//             if (isset($conn)) {
//                 $conn->rollback();
//             } 
//             $this->sendResponse("error", $e->getMessage());
//             echo "Update failed: " . $e->getMessage();
//         } finally {
//             if (isset($userStmt))
//                 $userStmt->close();
//             if (isset($adminStmt))
//                 $adminStmt->close();
//             if (isset($conn))
//                 $conn->close();
//         }



//     }
//     public function UpdateCustomer($requestData)
//     {
//         //filter based on whatever
//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }


//             $fields = [];
//             $cusFields =[];
//             $values = [];
//             $custValues = [];
//             $types = "";
//             $custTypes = "";
//             $fieldMap = [
//                 "name" => "s",
//                 "surname" => "s",
//                 "phone_number" => "s",
//                 "apikey" => "s",
//                 "email" => "s",
//                 "password" => "s",
//                 "street_number" => "s",
//                 "street_name" => "s",
//                 "suburb" => "s",
//                 "city" => "s",
//                 "zip_code" => "s",
//                 "user_type" => "s"
//             ];
//             $cusFieldMap = [

//                 "profile_picture" => "s"
//             ];

//             foreach ($fieldMap as $key => $type) {
//                 if (isset($requestData[$key])) {
//                     $fields[] = "$key = ?";
//                     $values[] = $requestData[$key];
//                     $types .= $type;
//                 }
//             }

//             foreach ($cusFieldMap as $key => $type) {
//                 if (isset($requestData[$key])) {
//                     $cusFields[] = "$key = ?";
//                     $custValues[] = $requestData[$key];
//                     $custTypes .= $type;
//                 }
//             }

//             if(count($fields) > 0 && isset($fieldMap['id']))
//             {

//             $conn->begin_transaction();

//             // Update User table
//             $userSql = "UPDATE User SET ". implode(", ", $fields) . "WHERE id=?";
//             $userStmt = $conn->prepare($userSql);
//             $userStmt->bind_param($types, $values);
//             $userStmt->execute();

//             // Update Admin table
//             $adminSql = "UPDATE Admin SET " . implode(", ", $cusFields) ." WHERE user_id=?";
//             $adminStmt = $conn->prepare($adminSql);
//             $adminStmt->bind_param($custTypes, $custValues, $fieldMap['id']); // d for double (salary), s for string, i for int
//             $adminStmt->execute();

//             $conn->commit();

//             $conn->close();

//             }
//         } catch (Exception $e) {
//             if (isset($conn)) {
//                 $conn->rollback();
//             }
//                         $this->sendResponse("error", $e->getMessage());

//             echo "Update failed: " . $e->getMessage();
//         } finally {
//             if (isset($userStmt))
//                 $userStmt->close();
//             if (isset($cusStmt))
//                 $cusStmt->close();
//             if (isset($conn))
//                 $conn->close();
//         }



//     }
//     public function ViewSupplier($requestData)
//     {
//         //filter based on whatever
//         try {

//             $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
//             if ($conn->connect_error) {
//                 die("Connection failed: " . $conn->connect_error);
//             }
//         } catch (mysqli_sql_exception $e) {
//             echo "Connection failed: " . $e->getMessage();
//             exit;
//         }

//         $productid = isset($requestData["id"]) ? $requestData["id"] : "";
//         $retailid = isset($requestData["retailer_id"]) ? $requestData["retailer_id"] : "";

//         $stmt = $conn->prepare("SELECT * FROM Retailer WHERE id = ?");
//         $stmt->bind_param("i", $retailid);
//         $stmt->execute();
//         $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);        
//         if (!empty($result)) {
//             $this->sendResponse("success",  $result);
//         }
//         $this->sendResponse("error", "No results found");

//         $stmt->close();
//         $conn->close();
//     }








//     ///////////

// }


// $api = new API();

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $requestData = json_decode(file_get_contents('php://input'), true);

//     if (isset($requestData['type'])) {
//         $apiKey = isset($requestData['apikey']) ? $requestData['apikey'] :'null';
//         if($apikey === 'null')
//         {
//             $api->sendResponse('error','No apikey');
//         }

//         $stmt = $conn->prepare('SELECT * FROM User WHERE apikey=?');
//         $stmt->bind_param('s', $apiKey);
//         $stmt->execute();
//         $result = $stmt->get_result();
//         if($result->num_rows <= 0)
//         {
//             $api->sendResponse('error','Invalid API key');
//         }
//         $stmt->close();
//         $conn->close();
        
//         if ($requestData['type'] === "Test") { //done
//             $api->TestResponse($requestData);
//         } else if ($requestData['type'] === "Login") { //50% done
//             $api->Login($requestData);
//         } else if ($requestData['type'] === "Register") { //50% done
//             $api->Register($requestData);
//         } else if ($requestData['type'] === "ViewAllProducts") { //done
//             $api->ViewAllProducts($requestData);
//         } else if ($requestData['type'] === "RateProduct") { //done
//             $api->RateProduct($requestData);
//         } else if ($requestData['type'] === "AddProduct") {
//             $api->AddProduct($requestData);
//         } else if ($requestData['type'] === "UpdateProduct") {
//             $api->UpdateProduct($requestData);
//         } else if ($requestData['type'] === "DeleteProduct") {
//             $api->DeleteProduct($requestData);
//         } else if ($requestData['type'] === "ViewRatings") { //done
//             $api->ViewRatings($requestData);
//         } else if ($requestData['type'] === "FilterProducts") {
//             $api->FilterProducts($requestData);
//         } else if ($requestData['type'] === "UpdateCustomer") { //50% done
//             $api->UpdateCustomer($requestData);
//         } else if ($requestData['type'] === "UpdateAdmin") { //50% done
//             $api->UpdateAdmin($requestData);
//         } else if ($requestData['type'] === "ViewSupplier") { //done
//             $api->ViewSupplier($requestData);
//         } else {
//             echo "please specify type";
//         }
//     } else {
//         http_response_code(400);
//         $api->sendResponse("error", "Missing or invalid parameters");
//     }
// } else {
//     http_response_code(405);
//     $api->sendResponse("error", "Method not allowed");
// }




?>