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
        if (!in_array($requiredUserType, ['Customer', 'Admin', 'Both'])) {
            ResponseAPI::error('Invalid User type to check if user is authorized', 400);
        }

        //Validate the API key
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('SELECT name, user_type FROM User WHERE apikey=?');
        $stmt->bind_param('s', $apikey);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows != 1) {
            ResponseAPI::error('Invalid API key or User not found', null, 401);
        }

        //Check if the user type is valid (not case sensitive)
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($requiredUserType !== 'Both' && strcasecmp($user['user_type'], $requiredUserType) !== 0) {
                ResponseAPI::error("User type ({$user['user_type']}) not allowed to use this request", null, 403);
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

    public static function login($requestData) {
        $errors = [];
        $validFields = [];

        //Validate email and password (do not check password against its regex)
        if(isset($requestData['email'])) {
            $validationResult = self::validateField('email', $requestData['email']);
            if ($validationResult !== true) {
                $errors['email'] = $validationResult;
            } else {
                $validFields['email'] = trim($requestData['email']);
            }
        }
        if (isset($requestData['password'])) {
            $validFields['password'] = $requestData['password'];
        }

        // Check required fields are present in the request
        foreach (['email', 'password'] as $field) {
            if (!array_key_exists($field, $requestData)) {
                $errors[$field] = "Error: The $field field is required.";
            }
        }

        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422);
        }

        // Check if email exists in database
        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id, name, password, salt, apikey, user_type FROM User WHERE email = ?");
        $stmt->bind_param("s", $validFields['email']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Invalid email or password", null, 401); //401 Unauthorized
        }

        $stmt->bind_result($user_id, $name, $hashedPassword, $salt, $apikey, $user_type);
        $stmt->fetch();

        // Hash the provided password with the salt and verify
        if (!password_verify($validFields['password'] . $salt, $hashedPassword)) {
            $stmt->close();
            ResponseAPI::error("Invalid email or password", null, 401); //401 Unauthorized
        }

        $stmt->close();

        ResponseAPI::send(
            "User logged in successfully",
            [
                'apikey' => $apikey,
                'name' => $name,
                'user_type' => $user_type
            ],
            200
        );
    }//login

}//USER class

class ADMIN {
    // Validation rules for QuickAddUser
    private static $quickAddUserRules = [
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
        'user_type' => [
            'required' => true,
            'pattern' => '/^(Admin|Customer)$/',
            'message' => 'User type must be either Admin or Customer'
        ],
        'password' => [
            'required' => true,
            'pattern' => "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/",
            'message' => 'Password must be at least 8 characters with upper/lower case, number, and special character'
        ],
        'email' => [
            'required' => true,
            'pattern' => FILTER_VALIDATE_EMAIL,
            'message' => 'Email must be valid and max 100 characters',
            'max_length' => 100
        ]
    ];

    public static function QuickAddUser($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Validate fields
        $errors = [];
        $fields = [];
        foreach (self::$quickAddUserRules as $field => $rule) {
            $value = isset($requestData[$field]) ? trim($requestData[$field]) : '';
            if ($rule['required'] && $value === '') {
                $errors[$field] = "Error: The $field field is required.";
            } elseif ($field === 'email') {
                // Email validation
                if (strlen($value) > $rule['max_length'] || !filter_var($value, $rule['pattern'])) {
                    $errors[$field] = $rule['message'];
                } else {
                    $fields[$field] = $value;
                }
            } elseif (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = $rule['message'];
            } else {
                $fields[$field] = $value;
            }
        }
        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422); //422 Unprocessable Entity
        }

        //Check if email already exists
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT id FROM User WHERE email = ?");
        $stmt->bind_param("s", $fields['email']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            ResponseAPI::error("Email already exists: Please use a different email", null, 409); //409 Conflict
        }
        $stmt->close();

        // Generate apikey and salt and hash password
        $salt = bin2hex(random_bytes(16));
        $passwordWithSalt = $fields['password'] . $salt;
        $hashedPassword = password_hash($passwordWithSalt, PASSWORD_DEFAULT);
        $apikey = bin2hex(random_bytes(32));

        // Insert into User table
        $stmt = $conn->prepare("INSERT INTO User (name, surname, email, user_type, password, salt, apikey) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $fields['name'], $fields['surname'], $fields['email'], $fields['user_type'], $hashedPassword, $salt, $apikey);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to add user: " . $conn->error, ['database_error' => $conn->error], 500); //500 Internal Server Error
        }
        $user_id = $conn->insert_id;
        $stmt->close();

        // Insert into Customer or Admin_staff table
        if ($fields['user_type'] === 'Customer') {
            $stmt = $conn->prepare("INSERT INTO Customer (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                $stmt->close();
                ResponseAPI::error("Failed to add customer: " . $conn->error, ['database_error' => $conn->error], 500);
            }
            $stmt->close();
        } else if ($fields['user_type'] === 'Admin') {
            $stmt = $conn->prepare("INSERT INTO Admin_staff (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                $stmt->close();
                ResponseAPI::error("Failed to add admin staff: " . $conn->error, ['database_error' => $conn->error], 500);
            }
            $stmt->close();
        }

        ResponseAPI::send("User added successfully", [
            'user_id' => $user_id,
            'apikey' => $apikey
        ], 201);//201 Created
    }//QuickAddUser

    public static function QuickEditProductPrice($requestData) {
        // Authenticate as Admin
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Validate input
        $errors = [];
        if (!isset($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            $errors['product_id'] = "Product ID is required and must be an integer.";
        }
        if (!isset($requestData['retailer_id']) || !is_numeric($requestData['retailer_id'])) {
            $errors['retailer_id'] = "Retailer ID is required and must be an integer.";
        }
        if (!isset($requestData['price']) || !is_numeric($requestData['price'])) {
            $errors['price'] = "Price is required and must be a floating point number.";
        }
        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422);
        }
        $product_id = (int)$requestData['product_id'];
        $retailer_id = (int)$requestData['retailer_id'];
        $price = (float)$requestData['price'];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if product exists
        $stmt = $conn->prepare("SELECT id FROM Product WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Product does not exist", null, 404); //404 Not Found
        }
        $stmt->close();

        // Check if retailer exists
        $stmt = $conn->prepare("SELECT id FROM Retailer WHERE id = ?");
        $stmt->bind_param("i", $retailer_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Retailer does not exist", null, 404); //404 Not Found
        }
        $stmt->close();

        // Check if SUPPLIED_BY entry exists
        $stmt = $conn->prepare("SELECT * FROM Supplied_By WHERE product_id = ? AND retailer_id = ?");
        $stmt->bind_param("ii", $product_id, $retailer_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Update price
            $stmt->close();
            $stmt = $conn->prepare("UPDATE Supplied_By SET price = ? WHERE product_id = ? AND retailer_id = ?");
            $stmt->bind_param("dii", $price, $product_id, $retailer_id);
            if (!$stmt->execute()) {
                $stmt->close();
                ResponseAPI::error("Failed to update price: " . $conn->error, ['database_error' => $conn->error], 500);
            }
            $stmt->close();
            ResponseAPI::send("Product price updated successfully", ['product_id' => $product_id, 'retailer_id' => $retailer_id, 'price' => $price], 200);
        } else {
            // Insert new price
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO Supplied_By (product_id, retailer_id, price) VALUES (?, ?, ?)");
            $stmt->bind_param("iid", $product_id, $retailer_id, $price);
            if (!$stmt->execute()) {
                $stmt->close();
                ResponseAPI::error("Failed to add price: " . $conn->error, ['database_error' => $conn->error], 500);
            }
            $stmt->close();
            ResponseAPI::send("Product price added successfully", ['product_id' => $product_id, 'retailer_id' => $retailer_id, 'price' => $price], 201);
        }
    }//QuickEditProductPrice

    public static function AdminRecentReviews($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401); //401 Unauthorized
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Default number of reviews to fetch is 4, or the number specified in the request
        $limit = 4;
        if (isset($requestData['number']) && is_numeric($requestData['number']) && $requestData['number'] > 0) {
            $limit = (int)$requestData['number'];
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Fetch the most recent reviews using updated_at
        $query = "SELECT * FROM Rating ORDER BY updated_at DESC LIMIT ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $reviews = [];
        while ($row = $res->fetch_assoc()) {
            $reviews[] = $row;
        }
        $stmt->close();

        ResponseAPI::send("Recent reviews fetched successfully", $reviews, 200);
    }//AdminRecentReviews

    public static function AddNewProduct($requestData) {
        // Authenticate as Admin
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Validate product fields
        $errors = [];
        $fields = [];

        if (empty($requestData['name'])) {
            $errors['name'] = "Product name is required.";
        } elseif (strlen($requestData['name']) > 100) {
            $errors['name'] = "Product name must be at most 100 characters.";
        } else {
            $fields['name'] = trim($requestData['name']);
        }

        $optionalFields = [
            'description' => ['max_length' => null], 
            'image_url' => ['max_length' => 255],
            'category' => ['max_length' => 100]
        ];

        foreach ($optionalFields as $field => $rules) {
            if (isset($requestData[$field])) {
                $value = trim($requestData[$field]);
                if ($rules['max_length'] !== null && strlen($value) > $rules['max_length']) {
                    $errors[$field] = "{$field} must be at most {$rules['max_length']} characters.";
                } else {
                    $fields[$field] = $value;
                }
            } else {
                $fields[$field] = null;
            }
        }

        // Validate retailer_id and price if provided
        $retailer_id = null;
        $price = null;
        $retailerExists = true;

        if (isset($requestData['retailer_id']) || isset($requestData['price'])) {
            // Check if both are provided
            if (!isset($requestData['retailer_id']) || !isset($requestData['price'])) {
                $errors['retailer_id'] = "Retailer ID and price must be provided together.";
                $errors['price'] = "Retailer ID and price must be provided together.";
            } else {
                // Validate retailer_id is numeric
                if (!is_numeric($requestData['retailer_id'])) {
                    $errors['retailer_id'] = "Retailer ID must be an integer.";
                } else {
                    $retailer_id = (int)$requestData['retailer_id'];
                }

                // Validate price is numeric
                if (!is_numeric($requestData['price'])) {
                    $errors['price'] = "Price must be a numeric value.";
                } else {
                    $price = (float)$requestData['price'];
                }

                // If both are valid format, check if retailer exists
                if (empty($errors['retailer_id']) && empty($errors['price'])) {
                    $db = Database::getInstance();
                    $conn = $db->getConnection();
                    $stmt = $conn->prepare("SELECT id FROM Retailer WHERE id = ?");
                    $stmt->bind_param("i", $retailer_id);
                    $stmt->execute();
                    $stmt->store_result();
                    $retailerExists = ($stmt->num_rows > 0);
                    $stmt->close();

                    if (!$retailerExists) {
                        $errors['retailer_id'] = "Retailer ID does not exist.";
                    }
                }
            }
        }

        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422);
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT id FROM Product WHERE name = ?");
        $stmt->bind_param("s", $fields['name']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($product_id);
            $stmt->fetch();
            $stmt->close();

            // If retailer_id and price are both set and valid
            if ($retailer_id !== null && $price !== null && $retailerExists) {
                // Check if the product-retailer relationship already exists
                $stmt = $conn->prepare("SELECT * FROM Supplied_By WHERE product_id = ? AND retailer_id = ?");
                $stmt->bind_param("ii", $product_id, $retailer_id);
                $stmt->execute();
                $stmt->store_result();
                $exists = ($stmt->num_rows > 0);
                $stmt->close();

                if ($exists) {
                    // Update existing price
                    $stmt = $conn->prepare("UPDATE Supplied_By SET price = ? WHERE product_id = ? AND retailer_id = ?");
                    $stmt->bind_param("dii", $price, $product_id, $retailer_id);
                } else {
                    // Insert new price
                    $stmt = $conn->prepare("INSERT INTO Supplied_By (product_id, retailer_id, price) VALUES (?, ?, ?)");
                    $stmt->bind_param("iid", $product_id, $retailer_id, $price);
                }

                if (!$stmt->execute()) {
                    $stmt->close();
                    ResponseAPI::error("Failed to update product price: " . $conn->error, ['database_error' => $conn->error], 500);
                }
                $stmt->close();

                ResponseAPI::send("Product price updated successfully", [
                    'product_id' => $product_id,
                    'retailer_id' => $retailer_id,
                    'price' => $price
                ], 200);
            } else {
                // Just return the product info
                ResponseAPI::send("Product already exists", [
                    'product_id' => $product_id,
                    'name' => $fields['name'],
                    'description' => $fields['description'],
                    'image_url' => $fields['image_url'],
                    'category' => $fields['category']
                ], 200);
            }
            return;
        }
        $stmt->close();

        $conn->begin_transaction();
        try {
            // Insert into Product table
            $stmt = $conn->prepare("INSERT INTO Product (name, description, image_url, category) VALUES (?, ?, ?, ?)");
            $stmt->bind_param(
                "ssss",
                $fields['name'],
                $fields['description'],
                $fields['image_url'],
                $fields['category']
            );

            if (!$stmt->execute()) {
                throw new Exception("Failed to add product: " . $conn->error);
            }
            $product_id = $conn->insert_id;
            $stmt->close();

            // If retailer_id and price are both set and valid, add to Supplied_By table
            if ($retailer_id !== null && $price !== null && $retailerExists) {
                $stmt = $conn->prepare("INSERT INTO Supplied_By (product_id, retailer_id, price) VALUES (?, ?, ?)");
                $stmt->bind_param("iid", $product_id, $retailer_id, $price);

                if (!$stmt->execute()) {
                    throw new Exception("Failed to add product price: " . $conn->error);
                }
                $stmt->close();
            }

            // Commit transaction if all operations succeeded
            $conn->commit();

            // Prepare response data
            $responseData = [
                'product_id' => $product_id,
                'name' => $fields['name'],
                'description' => $fields['description'],
                'image_url' => $fields['image_url'],
                'category' => $fields['category']
            ];

            if ($retailer_id !== null && $price !== null) {
                $responseData['retailer_id'] = $retailer_id;
                $responseData['price'] = $price;
                ResponseAPI::send("Product and price added successfully", $responseData, 201); //201 Created
            } else {
                ResponseAPI::send("Product added successfully", $responseData, 201); //201 Created
            }

        } catch (Exception $e) {
            // Rollback transaction on any error
            $conn->rollback();
            ResponseAPI::error("Database error:" . $e->getMessage(), ['database_error' => $conn->error], 500);//500 Internal Server Error
        }
    }//AddNewProduct

    public static function getAllProducts($requestData) {
        // Authenticate as Admin
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        $query = "SELECT * FROM Product";
        $result = $conn->query($query);

        if (!$result) {
            ResponseAPI::error("Failed to fetch products: " . $conn->error, ['database_error' => $conn->error], 500);
        }

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        ResponseAPI::send("All products fetched successfully", $products, 200);
    }// getAllProducts

    public static function deleteProduct($requestData) {
        // Authenticate as Admin
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Validate input
        if (empty($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            ResponseAPI::error("Product ID is required and must be an integer.", null, 422);
        }
        $product_id = (int)$requestData['product_id'];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if product exists
        $stmt = $conn->prepare("SELECT id FROM Product WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Product does not exist", null, 404);
        }
        $stmt->close();

        // Delete all ratings for this product
        $stmt = $conn->prepare("DELETE FROM Rating WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        // Delete all supplied_by entries for this product
        $stmt = $conn->prepare("DELETE FROM Supplied_By WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        // Delete all watchlist entries for this product
        $stmt = $conn->prepare("DELETE FROM watchlist WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();

        // Delete the product
        $stmt = $conn->prepare("DELETE FROM Product WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to delete product: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Product deleted successfully", ['product_id' => $product_id], 200);
    }//deleteProduct

    public static function GetAllRetailers($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        $query = "SELECT * FROM Retailer ORDER BY name ASC";
        $result = $conn->query($query);

        if (!$result) {
            ResponseAPI::error("Failed to fetch retailers: " . $conn->error, ['database_error' => $conn->error], 500);
        }

        $retailers = [];
        while ($row = $result->fetch_assoc()) {
            $retailers[] = $row;
        }

        ResponseAPI::send("All retailers fetched successfully", $retailers, 200);
    }// GetAllRetailers

    public static function AddRetailer($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Validation
        $errors = [];
        $fields = [];

        // Required fields
        if (empty($requestData['name']) || strlen($requestData['name']) > 100) {
            $errors['name'] = "Retailer name is required and must be at most 100 characters.";
        } else {
            $fields['name'] = trim($requestData['name']);
        }
        if (empty($requestData['email']) || strlen($requestData['email']) > 100 || !filter_var($requestData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Valid email is required and must be at most 100 characters.";
        } else {
            $fields['email'] = trim($requestData['email']);
        }

        // Optional fields
        $optionalFields = [
            'street_number' => 100,
            'street_name'   => 100,
            'suburb'        => 100,
            'city'          => 100,
            'zip_code'      => 10
        ];
        foreach ($optionalFields as $field => $maxLen) {
            if (isset($requestData[$field])) {
                $value = trim($requestData[$field]);
                if (strlen($value) > $maxLen) {
                    $errors[$field] = "$field must be at most $maxLen characters.";
                } else {
                    $fields[$field] = $value;
                }
            } else {
                $fields[$field] = null;
            }
        }

        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422);
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if retailer already exists by name
        $stmt = $conn->prepare("SELECT id FROM Retailer WHERE name = ?");
        $stmt->bind_param("s", $fields['name']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            ResponseAPI::error("Retailer already exists with this name.", null, 409); //409 Conflict
        }
        $stmt->close();

        // Insert retailer
        $stmt = $conn->prepare("INSERT INTO Retailer (name, email, street_number, street_name, suburb, city, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssssss",
            $fields['name'],
            $fields['email'],
            $fields['street_number'],
            $fields['street_name'],
            $fields['suburb'],
            $fields['city'],
            $fields['zip_code']
        );
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to add retailer: " . $conn->error, ['database_error' => $conn->error], 500); //500 Internal Server Error
        }
        $retailer_id = $conn->insert_id;
        $stmt->close();

        ResponseAPI::send("Retailer added successfully", [
            'retailer_id' => $retailer_id,
            'name' => $fields['name'],
            'email' => $fields['email'],
            'street_number' => $fields['street_number'],
            'street_name' => $fields['street_name'],
            'suburb' => $fields['suburb'],
            'city' => $fields['city'],
            'zip_code' => $fields['zip_code']
        ], 201);//201 Created
    }//AddRetailer

    public static function EditRetailer($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        if (empty($requestData['retailer_id']) || !is_numeric($requestData['retailer_id'])) {
            ResponseAPI::error("Retailer ID is required and must be an integer.", null, 422);
        }
        $retailer_id = (int)$requestData['retailer_id'];

        // Build update fields
        $fields = [];
        $values = [];
        $types = "";

        $fieldMap = [
            'name' => 100,
            'email' => 100,
            'street_number' => 100,
            'street_name' => 100,
            'suburb' => 100,
            'city' => 100,
            'zip_code' => 10
        ];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        foreach ($fieldMap as $field => $maxLen) {
            if (isset($requestData[$field])) {
                $value = trim($requestData[$field]);
                if ($field === 'email') {
                    if (strlen($value) > $maxLen || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        ResponseAPI::error("Email must be valid and at most $maxLen characters.", null, 422); //422 Unprocessable Entity
                    }
                } else if (strlen($value) > $maxLen) {
                    ResponseAPI::error("$field must be at most $maxLen characters.", null, 422);
                }
                // Check for duplicate name
                if ($field === 'name') {
                    $stmt = $conn->prepare("SELECT id FROM Retailer WHERE name = ? AND id != ?");
                    $stmt->bind_param("si", $value, $retailer_id);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $stmt->close();
                        ResponseAPI::error("Another retailer already exists with this name.", null, 409); //409 Conflict
                    }
                    $stmt->close();
                }
                $fields[] = "$field = ?";
                $values[] = $value;
                $types .= "s";
            }
        }

        if (empty($fields)) {
            ResponseAPI::error("No fields provided to update.", null, 422);
        }

        // Check if retailer exists
        $stmt = $conn->prepare("SELECT id FROM Retailer WHERE id = ?");
        $stmt->bind_param("i", $retailer_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Retailer does not exist.", null, 404);
        }
        $stmt->close();

        // Build and execute update query
        $sql = "UPDATE Retailer SET " . implode(", ", $fields) . " WHERE id = ?";
        $types .= "i";
        $values[] = $retailer_id;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to update retailer: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Retailer updated successfully", ['retailer_id' => $retailer_id], 200);
    }//EditRetailer

    public static function getAllUsers($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get all users sorted by id ASC
        $query = "SELECT * FROM User ORDER BY id ASC";
        $result = $conn->query($query);
        if (!$result) {
            ResponseAPI::error("Failed to fetch users: " . $conn->error, ['database_error' => $conn->error], 500);
        }

        $users = [];
        while ($user = $result->fetch_assoc()) {
            $user_id = $user['id'];
            $user_type = $user['user_type'];
            unset($user['password'], $user['salt'], $user['apikey']);
            // Get extra info from Customer or Admin_Staff
            if (strcasecmp($user_type, 'Customer') === 0) {
                // Customer
                $stmt = $conn->prepare("SELECT * FROM Customer WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $customerResult = $stmt->get_result();
                $customerData = $customerResult->fetch_assoc();
                $stmt->close();
                if ($customerData) {
                    unset($customerData['user_id']);
                    $user = array_merge($user, $customerData);
                }
            } else if (strcasecmp($user_type, 'Admin') === 0) {
                // Admin
                $stmt = $conn->prepare("SELECT * FROM Admin_staff WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $adminResult = $stmt->get_result();
                $adminData = $adminResult->fetch_assoc();
                $stmt->close();
                if ($adminData) {
                    unset($adminData['user_id']);
                    $user = array_merge($user, $adminData);
                }
            }
            $users[] = $user;
        }

        ResponseAPI::send("All users fetched successfully", $users, 200);
    }// getAllUsers

    public static function AddNewStaff($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Validation
        $errors = [];
        $fields = [];

        // Required fields
        $requiredFields = [
            'name' => ['pattern' => '/^[a-zA-Z]{1,50}$/', 'message' => 'Name must be only letters and max 50 characters'],
            'surname' => ['pattern' => '/^[a-zA-Z]{1,50}$/', 'message' => 'Surname must be only letters and max 50 characters'],
            'email' => ['pattern' => FILTER_VALIDATE_EMAIL, 'max_length' => 100, 'message' => 'Email must be valid and max 100 characters'],
            'phone_number' => ['pattern' => '/^\d{10}$/', 'message' => 'Phone number must be exactly 10 digits'],
            'password' => ['pattern' => "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/", 'message' => 'Password must be at least 8 characters with upper/lower case, number, and special character'],
            'position' => ['pattern' => '/^.{1,100}$/', 'message' => 'Position is required and must be at most 100 characters'],
            'salary' => ['pattern' => '/^\d+(\.\d{1,2})?$/', 'message' => 'Salary must be a valid number']
        ];

        foreach ($requiredFields as $field => $rule) {
            $value = isset($requestData[$field]) ? trim($requestData[$field]) : '';
            if ($value === '') {
                $errors[$field] = "Error: The $field field is required.";
            } else if ($field === 'email') {
                if (strlen($value) > $rule['max_length'] || !filter_var($value, $rule['pattern'])) {
                    $errors[$field] = $rule['message'];
                } else {
                    $fields[$field] = $value;
                }
            } else if ($field === 'salary') {
                if (!preg_match($rule['pattern'], $value)) {
                    $errors[$field] = $rule['message'];
                } else {
                    $fields[$field] = (float)$value;
                }
            } else if (!preg_match($rule['pattern'], $value)) {
                $errors[$field] = $rule['message'];
            } else {
                $fields[$field] = $value;
            }
        }

        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422);
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM User WHERE email = ?");
        $stmt->bind_param("s", $fields['email']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            ResponseAPI::error("Email already exists: Please use a different email", null, 409);
        }
        $stmt->close();

        // Generate apikey and salt and hash password
        $salt = bin2hex(random_bytes(16));
        $passwordWithSalt = $fields['password'] . $salt;
        $hashedPassword = password_hash($passwordWithSalt, PASSWORD_DEFAULT);
        $apikey = bin2hex(random_bytes(32));

        // Insert into User table
        $stmt = $conn->prepare("INSERT INTO User (name, surname, email, phone_number, user_type, password, salt, apikey) VALUES (?, ?, ?, ?, 'Admin', ?, ?, ?)");
        $stmt->bind_param("sssssss", $fields['name'], $fields['surname'], $fields['email'], $fields['phone_number'], $hashedPassword, $salt, $apikey);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to add staff to user table: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $user_id = $conn->insert_id;
        $stmt->close();

        // Insert into Admin_staff table
        $stmt = $conn->prepare("INSERT INTO Admin_staff (user_id, position, salary) VALUES (?, ?, ?)");
        $stmt->bind_param("isd", $user_id, $fields['position'], $fields['salary']);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to add admin staff: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Staff member added successfully", [
            'user_id' => $user_id,
            'apikey' => $apikey
        ], 201);
    }//AddNewStaff

    public static function editUser($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401); //401 Unauthorized
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        if (empty($requestData['id']) || !is_numeric($requestData['id'])) {
            ResponseAPI::error("User ID is required and must be an integer.", null, 422);//422 Unprocessable Entity
        }
        $user_id = (int)$requestData['id'];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get current user_type
        $stmt = $conn->prepare("SELECT user_type FROM User WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($current_type);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User does not exist.", null, 404);//404 Not Found
        }
        $stmt->close();

        // Validation for updatable fields
        $userFields = [
            'name' => ['pattern' => '/^[a-zA-Z]{1,50}$/', 'message' => 'Name must be only letters and max 50 characters'],
            'surname' => ['pattern' => '/^[a-zA-Z]{1,50}$/', 'message' => 'Surname must be only letters and max 50 characters'],
            'email' => ['pattern' => FILTER_VALIDATE_EMAIL, 'max_length' => 100, 'message' => 'Email must be valid and max 100 characters'],
            'phone_number' => ['pattern' => '/^\d{10}$/', 'message' => 'Phone number must be exactly 10 digits'],
            'password' => ['pattern' => "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/", 'message' => 'Password must be at least 8 characters with upper/lower case, number, and special character'],
            'user_type' => ['pattern' => '/^(Admin|Customer)$/', 'message' => 'User type must be either Admin or Customer']
        ];
        $adminFields = [
            'position' => ['pattern' => '/^.{1,100}$/', 'message' => 'Position must be at most 100 characters'],
            'salary' => ['pattern' => '/^\d+(\.\d{1,2})?$/', 'message' => 'Salary must be a valid number']
        ];

        $fields = [];
        $values = [];
        $types = "";

        foreach ($userFields as $field => $rule) {
            if (isset($requestData[$field])) {
                $value = trim($requestData[$field]);
                if ($field === 'email') {
                    if (strlen($value) > $rule['max_length'] || !filter_var($value, $rule['pattern'])) {
                        ResponseAPI::error($rule['message'], null, 422);
                    }
                    // Duplicate email check
                    $stmt = $conn->prepare("SELECT id FROM User WHERE email = ? AND id != ?");
                    $stmt->bind_param("si", $value, $user_id);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $stmt->close();
                        ResponseAPI::error("Email already exists for another user.", null, 409);
                    }
                    $stmt->close();
                } else if (!preg_match($rule['pattern'], $value)) {
                    ResponseAPI::error($rule['message'], null, 422);
                }
                if ($field === 'password') {
                    // Will be hashed below
                } else {
                    $fields[] = "$field = ?";
                    $values[] = $value;
                    $types .= "s";
                }
            }
        }

        // Handle password update
        if (isset($requestData['password'])) {
            $salt = bin2hex(random_bytes(16));
            $passwordWithSalt = $requestData['password'] . $salt;
            $hashedPassword = password_hash($passwordWithSalt, PASSWORD_DEFAULT);
            $fields[] = "password = ?";
            $values[] = $hashedPassword;
            $types .= "s";
            $fields[] = "salt = ?";
            $values[] = $salt;
            $types .= "s";
        }

        // Handle user_type change
        $user_type_changed = false;
        if (isset($requestData['user_type'])) {
            $new_type = $requestData['user_type'];
            if ($new_type !== $current_type) {
                $user_type_changed = true;
            }
        }

        if (!empty($fields)) {
            $sql = "UPDATE User SET " . implode(", ", $fields) . " WHERE id = ?";
            $types .= "i";
            $values[] = $user_id;
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$values);
            if (!$stmt->execute()) {
                $stmt->close();
                ResponseAPI::error("Failed to update user: " . $conn->error, ['database_error' => $conn->error], 500);
            }
            $stmt->close();
        }

        // Handle user_type table changes
        if ($user_type_changed) {
            // Remove from old table
            if ($current_type === 'Admin') {
                $stmt = $conn->prepare("DELETE FROM Admin_staff WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            } else if ($current_type === 'Customer') {
                $stmt = $conn->prepare("DELETE FROM Customer WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
            // Add to new table
            if ($new_type === 'Admin') {
                $stmt = $conn->prepare("INSERT INTO Admin_staff (user_id) VALUES (?)");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            } else if ($new_type === 'Customer') {
                $stmt = $conn->prepare("INSERT INTO Customer (user_id) VALUES (?)");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Update Admin_staff fields if user is admin and fields are provided
        if ((isset($requestData['position']) || isset($requestData['salary'])) && (isset($requestData['user_type']) ? $requestData['user_type'] === 'Admin' : $current_type === 'Admin')) {
            $adminUpdates = [];
            $adminValues = [];
            $adminTypes = "";
            foreach ($adminFields as $field => $rule) {
                if (isset($requestData[$field])) {
                    $value = trim($requestData[$field]);
                    if (!preg_match($rule['pattern'], $value)) {
                        ResponseAPI::error($rule['message'], null, 422);
                    }
                    $adminUpdates[] = "$field = ?";
                    if ($field === 'salary') {
                        $adminValues[] = (float)$value;
                        $adminTypes .= "d";
                    } else {
                        $adminValues[] = $value;
                        $adminTypes .= "s";
                    }
                }
            }
            if (!empty($adminUpdates)) {
                $adminTypes .= "i";
                $adminValues[] = $user_id;
                $sql = "UPDATE Admin_staff SET " . implode(", ", $adminUpdates) . " WHERE user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($adminTypes, ...$adminValues);
                if (!$stmt->execute()) {
                    $stmt->close();
                    ResponseAPI::error("Failed to update admin staff: " . $conn->error, ['database_error' => $conn->error], 500);
                }
                $stmt->close();
            }
        }

        ResponseAPI::send("User updated successfully", ['user_id' => $user_id], 200);
    }//editUser

    public static function deleteUser($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Validate user_id
        if (empty($requestData['user_id']) || !is_numeric($requestData['user_id'])) {
            ResponseAPI::error("User ID is required and must be an integer.", null, 422);
        }
        $user_id = (int)$requestData['user_id'];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if user exists and get user_type
        $stmt = $conn->prepare("SELECT user_type FROM User WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($user_type);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User does not exist", null, 404);
        }
        $stmt->close();

        // Delete all ratings by this user
        $stmt = $conn->prepare("DELETE FROM Rating WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Delete all watchlist entries for this user
        $stmt = $conn->prepare("DELETE FROM watchlist WHERE cust_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();

        // Delete from Customer or Admin_staff table
        if (strcasecmp($user_type, 'Customer') === 0) {
            $stmt = $conn->prepare("DELETE FROM Customer WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        } else if (strcasecmp($user_type, 'Admin') === 0) {
            $stmt = $conn->prepare("DELETE FROM Admin_staff WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // Delete from User table
        $stmt = $conn->prepare("DELETE FROM User WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to delete user: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("User deleted successfully", ['user_id' => $user_id], 200);
    }//deleteUser

    public static function deleteRating($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Identify review: by review_id OR by user_id + product_id
        $review_id = null;
        $user_id = null;
        $product_id = null;

        if (!empty($requestData['review_id']) && is_numeric($requestData['review_id'])) {
            $review_id = (int)$requestData['review_id'];
            // Fetch user_id and product_id for this review
            $stmt = $conn->prepare("SELECT user_id, product_id FROM Rating WHERE id = ?");
            $stmt->bind_param("i", $review_id);
            $stmt->execute();
            $stmt->bind_result($user_id, $product_id);
            if (!$stmt->fetch()) {
                $stmt->close();
                ResponseAPI::error("Rating does not exist for this review ID.", null, 404);
            }
            $stmt->close();
        } elseif (
            !empty($requestData['user_id']) && is_numeric($requestData['user_id']) &&
            !empty($requestData['product_id']) && is_numeric($requestData['product_id'])
        ) {
            $user_id = (int)$requestData['user_id'];
            $product_id = (int)$requestData['product_id'];
            // Check if rating exists
            $stmt = $conn->prepare("SELECT id FROM Rating WHERE user_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $stmt->bind_result($review_id);
            if (!$stmt->fetch()) {
                $stmt->close();
                ResponseAPI::error("Rating does not exist for this user and product.", null, 404);
            }
            $stmt->close();
        } else {
            ResponseAPI::error("Either review_id or both user_id and product_id are required.", null, 422);
        }

        // Delete the rating
        $stmt = $conn->prepare("DELETE FROM Rating WHERE id = ?");
        $stmt->bind_param("i", $review_id);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to delete rating: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Rating deleted successfully", [
            'review_id' => $review_id,
            'user_id' => $user_id,
            'product_id' => $product_id
        ], 200);
    }//deleteRating

    public static function deleteRetailer($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Validate input
        if (empty($requestData['retailer_id']) || !is_numeric($requestData['retailer_id'])) {
            ResponseAPI::error("Retailer ID is required and must be an integer.", null, 422);
        }
        $retailer_id = (int)$requestData['retailer_id'];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if retailer exists
        $stmt = $conn->prepare("SELECT id FROM Retailer WHERE id = ?");
        $stmt->bind_param("i", $retailer_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Retailer does not exist.", null, 404);
        }
        $stmt->close();

        // Delete all supplied_by entries for this retailer
        $stmt = $conn->prepare("DELETE FROM Supplied_By WHERE retailer_id = ?");
        $stmt->bind_param("i", $retailer_id);
        $stmt->execute();
        $stmt->close();

        // Delete the retailer
        $stmt = $conn->prepare("DELETE FROM Retailer WHERE id = ?");
        $stmt->bind_param("i", $retailer_id);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to delete retailer: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Retailer deleted successfully", ['retailer_id' => $retailer_id], 200);
    }//deleteRetailer

    public static function editProduct($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Admin');

        // Validate product_id
        if (empty($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            ResponseAPI::error("Product ID is required and must be an integer.", null, 422);
        }
        $product_id = (int)$requestData['product_id'];

        // Validate optional fields
        $errors = [];
        $fields = [];
        $values = [];
        $types = "";

        // Check if product exists
        $db = Database::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT id FROM Product WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Product does not exist.", null, 404);
        }
        $stmt->close();

        // Validation rules
        $fieldRules = [
            'name' => ['max_length' => 100, 'message' => 'Product name must be at most 100 characters.'],
            'description' => ['max_length' => null],
            'image_url' => ['max_length' => 255, 'message' => 'Image URL must be at most 255 characters.'],
            'category' => ['max_length' => 100, 'message' => 'Category must be at most 100 characters.']
        ];

        // Check for duplicate name if name is being updated
        if (isset($requestData['name'])) {
            $newName = trim($requestData['name']);
            if (strlen($newName) > $fieldRules['name']['max_length']) {
                $errors['name'] = $fieldRules['name']['message'];
            } else {
                // Check if another product with this name exists (excluding this product)
                $stmt = $conn->prepare("SELECT id FROM Product WHERE name = ? AND id != ?");
                $stmt->bind_param("si", $newName, $product_id);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $stmt->close();
                    ResponseAPI::error("Product name already exists. Please use a different name.", null, 409);
                }
                $stmt->close();
                $fields[] = "name = ?";
                $values[] = $newName;
                $types .= "s";
            }
        }

        // Validate and add other fields
        foreach (['description', 'image_url', 'category'] as $field) {
            if (isset($requestData[$field])) {
                $value = trim($requestData[$field]);
                $maxLen = $fieldRules[$field]['max_length'];
                if ($maxLen !== null && strlen($value) > $maxLen) {
                    $errors[$field] = $fieldRules[$field]['message'];
                } else {
                    $fields[] = "$field = ?";
                    $values[] = $value;
                    $types .= "s";
                }
            }
        }

        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422);
        }

        if (empty($fields)) {
            ResponseAPI::error("No fields provided to update.", null, 422);
        }

        // Build and execute update query
        $sql = "UPDATE Product SET " . implode(", ", $fields) . " WHERE id = ?";
        $types .= "i";
        $values[] = $product_id;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to update product: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Product updated successfully", ['product_id' => $product_id], 200);
    }//editProduct
}

class CUSTOMER {
    public static function getAllCategories($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Both');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        $query = "SELECT DISTINCT category FROM Product WHERE category IS NOT NULL AND category != '' ORDER BY category ASC";
        $result = $conn->query($query);

        if (!$result) {
            ResponseAPI::error("Failed to fetch categories: " . $conn->error, ['database_error' => $conn->error], 500);
        }

        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }

        ResponseAPI::send("All categories fetched successfully", $categories, 200); //200 OK
    }// getAllCategories

    public static function getAllProducts($requestData) {
        // Only customers can use this endpoint - customers are routed here by the switch statement
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Filters and options
        $name = isset($requestData['name']) ? '%' . $conn->real_escape_string($requestData['name']) . '%' : null;
        $category = isset($requestData['category']) ? $conn->real_escape_string($requestData['category']) : null;
        $sort_by = isset($requestData['sort_by']) ? $requestData['sort_by'] : null;
        $include_no_price = isset($requestData['include_no_price']) ? (bool)$requestData['include_no_price'] : true;
        $include_no_rating = isset($requestData['include_no_rating']) ? (bool)$requestData['include_no_rating'] : true;
        $min_avg_rating = isset($requestData['filter_by']['minimum_average_rating']) ? (float)$requestData['filter_by']['minimum_average_rating'] : null;
        $limit = (isset($requestData['limit']) && is_numeric($requestData['limit']) && $requestData['limit'] > 0) ? (int)$requestData['limit'] : null;

        // Build base query:
        // 
        $sql = "
            SELECT 
                p.id AS product_id,
                p.name AS title,
                p.image_url,
                p.category,
                ROUND(AVG(r.score), 1) AS average_rating,
                sp.cheapest_price,
                sp.retailer_id,
                rt.name AS retailer_name
            FROM Product p
            LEFT JOIN Rating r ON r.product_id = p.id
            LEFT JOIN (
                SELECT sb.product_id, sb.retailer_id, sb.price AS cheapest_price
                FROM Supplied_By sb
                INNER JOIN (
                    SELECT product_id, MIN(price) AS min_price
                    FROM Supplied_By
                    GROUP BY product_id
                ) minp ON sb.product_id = minp.product_id AND sb.price = minp.min_price
            ) sp ON sp.product_id = p.id
            LEFT JOIN Retailer rt ON sp.retailer_id = rt.id
        ";
        //For each product in the product table, we left join the product table with the rating table 
        // to get the average rating, and the supplied_by table to get the cheapest price. 
        // The subquery in the supplied_by table gets the minimum price for each product and joins it 
        // with the retailer table to get the retailer's name.

        // Where conditions
        $where = [];
        $params = [];
        $types = "";

        if ($name) {
            $where[] = "p.name LIKE ?";
            $params[] = $name;
            $types .= "s";
        }
        if ($category) {
            $where[] = "p.category = ?";
            $params[] = $category;
            $types .= "s";
        }

        // Having clause for minimum average rating
        $having = [];
        if ($min_avg_rating !== null) {
            $having[] = "ROUND(AVG(r.score), 1) >= ?";
            $params[] = $min_avg_rating;
            $types .= "d";
            $include_no_rating = false;
            $include_no_price = false;
        }
        if (!$include_no_price) {
            $having[] = "sp.cheapest_price IS NOT NULL";
        }
        if (!$include_no_rating) {
            $having[] = "AVG(r.score) IS NOT NULL";
        }

        // Sorting
        $order = "";
        switch ($sort_by) {
        case "price_asc":
            $order = "ORDER BY (sp.cheapest_price IS NULL), sp.cheapest_price ASC";
            break;
        case "price_desc":
            $order = "ORDER BY (sp.cheapest_price IS NULL), sp.cheapest_price DESC";
            break;
        case "name_asc":
            $order = "ORDER BY p.name ASC";
            break;
        case "name_desc":
            $order = "ORDER BY p.name DESC";
            break;
        case "rating_asc":
            $order = "ORDER BY (AVG(r.score) IS NULL), ROUND(AVG(r.score), 1) ASC";
            break;
        case "rating_desc":
            $order = "ORDER BY (AVG(r.score) IS NULL), ROUND(AVG(r.score), 1) DESC";
            break;
        default:
            $order = "ORDER BY p.name ASC";
    }

        // Build final SQL
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " GROUP BY p.id";
        if (!empty($having)) {
            $sql .= " HAVING " . implode(" AND ", $having);
        }
        $sql .= " $order";
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            $types .= "i";
        }

        // Prepare and execute
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'product_id' => (int)$row['product_id'],
                'title' => $row['title'],
                'image_url' => $row['image_url'],
                'category' => $row['category'],
                'average_rating' => $row['average_rating'] !== null ? (float)number_format($row['average_rating'], 1) : null,
                'cheapest_price' => $row['cheapest_price'] !== null ? (float)number_format($row['cheapest_price'], 2, '.', '') : null,
                'retailer_id' => $row['retailer_id'] !== null ? (int)$row['retailer_id'] : null,
                'retailer_name' => $row['retailer_name'] !== null ? $row['retailer_name'] : null
            ];
        }
        $stmt->close();

        ResponseAPI::send("All products fetched successfully", $products, 200);
    }// getAllProducts

    public static function getMyDetails($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get user info
        $stmt = $conn->prepare("SELECT * FROM User WHERE apikey = ?");
        $stmt->bind_param("s", $requestData['apikey']);
        $stmt->execute();
        $userResult = $stmt->get_result();
        $user = $userResult->fetch_assoc();
        $stmt->close();

        if (!$user) {
            ResponseAPI::error("User not found.", null, 404); //404 Not Found
        }

        // Get customer info
        $stmt = $conn->prepare("SELECT * FROM Customer WHERE user_id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        $customerResult = $stmt->get_result();
        $customer = $customerResult->fetch_assoc();
        $stmt->close();

        if (!$customer) {
            ResponseAPI::error("Customer details not found.", null, 404); //404 Not Found
        }

        // Remove sensitive fields
        unset($user['password'], $user['salt'], $user['apikey']);

        // Merge and return
        $details = array_merge($user, $customer);
        ResponseAPI::send("Customer details fetched successfully", $details, 200); //200 OK
    }//getMyDetails

    public static function updateMyDetails($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get user id
        $stmt = $conn->prepare("SELECT id FROM User WHERE apikey = ?");
        $stmt->bind_param("s", $requestData['apikey']);
        $stmt->execute();
        $stmt->bind_result($user_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User not found.", null, 404);
        }
        $stmt->close();

        // Validation rules
        $rules = [
            'name' => ['pattern' => '/^[a-zA-Z]{1,50}$/', 'message' => 'Name must be only letters and max 50 characters'],
            'surname' => ['pattern' => '/^[a-zA-Z]{1,50}$/', 'message' => 'Surname must be only letters and max 50 characters'],
            'phone_number' => ['pattern' => '/^\d{10}$/', 'message' => 'Phone number must be exactly 10 digits'],
            'email' => ['pattern' => FILTER_VALIDATE_EMAIL, 'max_length' => 100, 'message' => 'Email must be valid and max 100 characters'],
            'password' => ['pattern' => "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/", 'message' => 'Password must be at least 8 characters with upper/lower case, number, and special character'],
            'street_number' => ['max_length' => 10, 'message' => 'Street number must be 10 characters or less'],
            'street_name' => ['pattern' => '/^[a-zA-Z\s]+$/', 'max_length' => 100, 'message' => 'Street name must be only letters and spaces, max 100 characters'],
            'suburb' => ['pattern' => '/^[a-zA-Z\s]+$/', 'max_length' => 100, 'message' => 'Suburb must be only letters and spaces, max 100 characters'],
            'city' => ['pattern' => '/^[a-zA-Z\s]+$/', 'max_length' => 100, 'message' => 'City must be only letters and spaces, max 100 characters'],
            'zip_code' => ['max_length' => 5, 'message' => 'Zip code must be 5 characters or less']
        ];

        $userFields = ['name', 'surname', 'phone_number', 'email', 'password', 'street_number', 'street_name', 'suburb', 'city', 'zip_code'];
        $fields = [];
        $values = [];
        $types = "";
        $errors = [];

        // Validate fields
        foreach ($userFields as $field) {
            if (isset($requestData[$field])) {
                $value = trim($requestData[$field]);
                $rule = $rules[$field];

                // Email validation
                if ($field === 'email') {
                    if (strlen($value) > $rule['max_length'] || !filter_var($value, $rule['pattern'])) {
                        ResponseAPI::error($rule['message'], null, 422);
                    }
                    // Duplicate email check
                    $stmt = $conn->prepare("SELECT id FROM User WHERE email = ? AND id != ?");
                    $stmt->bind_param("si", $value, $user_id);
                    $stmt->execute();
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $stmt->close();
                        ResponseAPI::error("Email already exists for another user.", null, 409); //409 Conflict
                    }
                    $stmt->close();
                } elseif ($field === 'password') {
                    if (!preg_match($rule['pattern'], $value)) {
                        $errors[$field] = $rule['message'];
                    }
                } elseif (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                    $errors[$field] = $rule['message'];
                } elseif (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[$field] = $rule['message'];
                }
            }
        }

        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422);
        }

        // Prepare update for User table
        $userUpdates = [];
        $userValues = [];
        $userTypes = "";

        foreach (['name', 'surname', 'phone_number', 'email', 'street_number', 'street_name', 'suburb', 'city', 'zip_code'] as $field) {
            if (isset($requestData[$field])) {
                $userUpdates[] = "$field = ?";
                $userValues[] = trim($requestData[$field]);
                $userTypes .= "s";
            }
        }

        // Handle password update
        if (isset($requestData['password'])) {
            $salt = bin2hex(random_bytes(16));
            $passwordWithSalt = $requestData['password'] . $salt;
            $hashedPassword = password_hash($passwordWithSalt, PASSWORD_DEFAULT);
            $userUpdates[] = "password = ?";
            $userValues[] = $hashedPassword;
            $userTypes .= "s";
            $userUpdates[] = "salt = ?";
            $userValues[] = $salt;
            $userTypes .= "s";
        }

        // Only update if there are fields to update
        if (!empty($userUpdates)) {
            $userTypes .= "i";
            $userValues[] = $user_id;
            $sql = "UPDATE User SET " . implode(", ", $userUpdates) . " WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($userTypes, ...$userValues);
            if (!$stmt->execute()) {
                $stmt->close();
                ResponseAPI::error("Failed to update user: " . $conn->error, ['database_error' => $conn->error], 500);
            }
            $stmt->close();
        }

        $customerUpdates = [];
        $customerValues = [];
        $customerTypes = "";

        if (!empty($customerUpdates)) {
            $customerTypes .= "i";
            $customerValues[] = $user_id;
            $sql = "UPDATE Customer SET " . implode(", ", $customerUpdates) . " WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($customerTypes, ...$customerValues);
            if (!$stmt->execute()) {
                $stmt->close();
                ResponseAPI::error("Failed to update customer: " . $conn->error, ['database_error' => $conn->error], 500);
            }
            $stmt->close();
        }

        ResponseAPI::send("Customer details updated successfully", ['user_id' => $user_id], 200);
    }//updateMyDetails

    public static function getMyReviews($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get user id
        $stmt = $conn->prepare("SELECT id FROM User WHERE apikey = ?");
        $stmt->bind_param("s", $requestData['apikey']);
        $stmt->execute();
        $stmt->bind_result($user_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User not found.", null, 404);
        }
        $stmt->close();

        // Get all reviews for this user, including product info and cheapest price/retailer
        $sql = "
            SELECT 
                r.id AS review_id,
                r.score,
                r.description,
                r.updated_at,
                p.id AS product_id,
                p.name AS product_name,
                p.image_url,
                sp.cheapest_price,
                sp.retailer_id,
                rt.name AS retailer_name
            FROM Rating r
            INNER JOIN Product p ON r.product_id = p.id
            LEFT JOIN (
                SELECT sb.product_id, sb.retailer_id, sb.price AS cheapest_price
                FROM Supplied_By sb
                INNER JOIN (
                    SELECT product_id, MIN(price) AS min_price
                    FROM Supplied_By
                    GROUP BY product_id
                ) minp ON sb.product_id = minp.product_id AND sb.price = minp.min_price
            ) sp ON sp.product_id = p.id
            LEFT JOIN Retailer rt ON sp.retailer_id = rt.id
            WHERE r.user_id = ?
            ORDER BY r.updated_at DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = [
                'review_id' => (int)$row['review_id'],
                'score' => (int)$row['score'],
                'description' => $row['description'],
                'last_updated' => $row['updated_at'],
                'product_id' => (int)$row['product_id'],
                'product_name' => $row['product_name'],
                'image_url' => $row['image_url'],
                'cheapest_price' => $row['cheapest_price'] !== null ? (float)number_format($row['cheapest_price'], 2, '.', '') : null,
                'retailer_id' => $row['retailer_id'] !== null ? (int)$row['retailer_id'] : null,
                'retailer_name' => $row['retailer_name'] !== null ? $row['retailer_name'] : null
            ];
        }
        $stmt->close();

        ResponseAPI::send("My reviews fetched successfully", $reviews, 200);
    }// getMyReviews

    public static function writeReview($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get user id
        $stmt = $conn->prepare("SELECT id FROM User WHERE apikey = ?");
        $stmt->bind_param("s", $requestData['apikey']);
        $stmt->execute();
        $stmt->bind_result($user_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User not found.", null, 404);
        }
        $stmt->close();

        // Validate fields
        $errors = [];
        if (empty($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            $errors['product_id'] = "Product ID is required and must be an integer.";
        }
        if (!isset($requestData['score']) || !is_numeric($requestData['score']) || $requestData['score'] < 1 || $requestData['score'] > 5) {
            $errors['score'] = "Score must be an integer between 1 and 5.";
        }
        if (empty($requestData['description']) || strlen(trim($requestData['description'])) < 10) {
            $errors['description'] = "Description must be at least 10 characters.";
        }
        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422);
        }
        $product_id = (int)$requestData['product_id'];
        $score = (int)$requestData['score'];
        $description = trim($requestData['description']);

        // Check product exists
        $stmt = $conn->prepare("SELECT id FROM Product WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Product does not exist.", null, 404);
        }
        $stmt->close();

        // Check if review already exists for this user and product
        $stmt = $conn->prepare("SELECT id FROM Rating WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $stmt->bind_result($existing_review_id);
        if ($stmt->fetch()) {
            // Review exists: update it
            $stmt->close();
            $stmt = $conn->prepare("UPDATE Rating SET score = ?, description = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("isi", $score, $description, $existing_review_id);
            if (!$stmt->execute()) {
                $stmt->close();
                ResponseAPI::error("Failed to update review: " . $conn->error, ['database_error' => $conn->error], 500);
            }
            $stmt->close();
            ResponseAPI::send("Review updated successfully", [
                'review_id' => $existing_review_id,
                'customer_id' => $user_id,
                'product_id' => $product_id
            ], 200);
        } else {
            $stmt->close();
            // Insert new review
            $stmt = $conn->prepare("INSERT INTO Rating (score, description, user_id, product_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isii", $score, $description, $user_id, $product_id);
            if (!$stmt->execute()) {
                $stmt->close();
                ResponseAPI::error("Failed to add review: " . $conn->error, ['database_error' => $conn->error], 500);
            }
            $review_id = $stmt->insert_id;
            $stmt->close();

            ResponseAPI::send("Review added successfully", [
                'review_id' => $review_id,
                'customer_id' => $user_id,
                'product_id' => $product_id
            ], 201);
        }
    }// writeReview

    public static function editMyReview($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get user id
        $stmt = $conn->prepare("SELECT id FROM User WHERE apikey = ?");
        $stmt->bind_param("s", $requestData['apikey']);
        $stmt->execute();
        $stmt->bind_result($user_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User not found.", null, 404);
        }
        $stmt->close();

        // Identify review: by review_id or by product_id + user_id
        $review_id = null;
        if (!empty($requestData['review_id']) && is_numeric($requestData['review_id'])) {
            $review_id = (int)$requestData['review_id'];
            $stmt = $conn->prepare("SELECT id, product_id FROM Rating WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $review_id, $user_id);
            $stmt->execute();
            $stmt->bind_result($found_review_id, $product_id);
            if (!$stmt->fetch()) {
                $stmt->close();
                ResponseAPI::error("Review not found for this user.", null, 404);
            }
            $stmt->close();
        } elseif (!empty($requestData['product_id']) && is_numeric($requestData['product_id'])) {
            $product_id = (int)$requestData['product_id'];
            $stmt = $conn->prepare("SELECT id FROM Rating WHERE product_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $product_id, $user_id);
            $stmt->execute();
            $stmt->bind_result($found_review_id);
            if (!$stmt->fetch()) {
                $stmt->close();
                ResponseAPI::error("Review not found for this user and product.", null, 404);
            }
            $review_id = $found_review_id;
            $stmt->close();
        } else {
            ResponseAPI::error("Review ID or Product ID is required.", null, 422);
        }

        // Validate fields
        $errors = [];
        if (isset($requestData['score']) && (!is_numeric($requestData['score']) || $requestData['score'] < 1 || $requestData['score'] > 5)) {
            $errors['score'] = "Score must be an integer between 1 and 5.";
        }
        if (isset($requestData['description']) && strlen(trim($requestData['description'])) < 10) {
            $errors['description'] = "Description must be at least 10 characters.";
        }
        if (!empty($errors)) {
            ResponseAPI::error("Parameter validation failed!", $errors, 422);
        }

        // Only update if at least one field is provided
        $fields = [];
        $values = [];
        $types = "";
        if (isset($requestData['score'])) {
            $fields[] = "score = ?";
            $values[] = (int)$requestData['score'];
            $types .= "i";
        }
        if (isset($requestData['description'])) {
            $fields[] = "description = ?";
            $values[] = trim($requestData['description']);
            $types .= "s";
        }
        if (empty($fields)) {
            ResponseAPI::error("No fields provided to update.", null, 422);
        }
        $types .= "i";
        $values[] = $review_id;

        $sql = "UPDATE Rating SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to update review: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Review updated successfully", [
            'review_id' => $review_id,
            'customer_id' => $user_id,
            'product_id' => isset($product_id) ? $product_id : null
        ], 200);
    }// editMyReview

    public static function deleteMyReview($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get user id
        $stmt = $conn->prepare("SELECT id FROM User WHERE apikey = ?");
        $stmt->bind_param("s", $requestData['apikey']);
        $stmt->execute();
        $stmt->bind_result($user_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User not found.", null, 404);
        }
        $stmt->close();

        // Identify review: by review_id or by product_id + user_id
        $review_id = null;
        $product_id = null;
        if (!empty($requestData['review_id']) && is_numeric($requestData['review_id'])) {
            $review_id = (int)$requestData['review_id'];
            $stmt = $conn->prepare("SELECT product_id FROM Rating WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $review_id, $user_id);
            $stmt->execute();
            $stmt->bind_result($product_id);
            if (!$stmt->fetch()) {
                $stmt->close();
                ResponseAPI::error("Review not found for this user.", null, 404);
            }
            $stmt->close();
        } elseif (!empty($requestData['product_id']) && is_numeric($requestData['product_id'])) {
            $product_id = (int)$requestData['product_id'];
            $stmt = $conn->prepare("SELECT id FROM Rating WHERE product_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $product_id, $user_id);
            $stmt->execute();
            $stmt->bind_result($review_id);
            if (!$stmt->fetch()) {
                $stmt->close();
                ResponseAPI::error("Review not found for this user and product.", null, 404);
            }
            $stmt->close();
        } else {
            ResponseAPI::error("Review ID or Product ID is required.", null, 422);
        }

        // Delete the review
        $stmt = $conn->prepare("DELETE FROM Rating WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $review_id, $user_id);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to delete review: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Review deleted successfully", [
            'review_id' => $review_id,
            'customer_id' => $user_id,
            'product_id' => $product_id
        ], 200);
    }// deleteMyReview

    public static function getProductDetails($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        if (empty($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            ResponseAPI::error("Product ID is required and must be an integer.", null, 422);
        }
        $product_id = (int)$requestData['product_id'];

        // Handle "return" parameter
        $returnType = isset($requestData['return']) ? ucfirst(strtolower($requestData['return'])) : 'All';
        $allowedReturnTypes = ['All', 'Product', 'Retailers', 'Reviews'];
        if (!in_array($returnType, $allowedReturnTypes)) {
            ResponseAPI::error("Invalid value for 'return' parameter. Allowed: All, Product, Retailers, Reviews.", null, 422);
        }

        // Sorting options for reviews
        $sort_reviews = isset($requestData['sort_reviews']) ? $requestData['sort_reviews'] : 'time_DESC';
        $allowed_review_sorts = [
            'time_ASC' => 'r.updated_at ASC',
            'time_DESC' => 'r.updated_at DESC',
            'score_ASC' => 'r.score ASC',
            'score_DESC' => 'r.score DESC',
            'name_ASC' => 'u.name ASC',
            'name_DESC' => 'u.name DESC'
        ];
        $review_order = isset($allowed_review_sorts[$sort_reviews]) ? $allowed_review_sorts[$sort_reviews] : $allowed_review_sorts['time_DESC'];

        // Sorting options for retailers
        $sort_retailers = isset($requestData['sort_retailers']) ? $requestData['sort_retailers'] : 'price_ASC';
        $allowed_retailer_sorts = [
            'name_ASC' => 'r.name ASC',
            'name_DESC' => 'r.name DESC',
            'price_ASC' => 'sb.price ASC',
            'price_DESC' => 'sb.price DESC'
        ];
        $retailer_order = isset($allowed_retailer_sorts[$sort_retailers]) ? $allowed_retailer_sorts[$sort_retailers] : $allowed_retailer_sorts['price_ASC'];

        // Filter reviews by score
        $filter_reviews_by_score = null;
        if (isset($requestData['filter_reviews_by_score'])) {
            if (!is_numeric($requestData['filter_reviews_by_score']) || $requestData['filter_reviews_by_score'] < 1 || $requestData['filter_reviews_by_score'] > 5) {
                ResponseAPI::error("filter_reviews_by_score must be an integer between 1 and 5.", null, 422);
            }
            $filter_reviews_by_score = (int)$requestData['filter_reviews_by_score'];
        }

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if product exists and get all product fields
        $stmt = $conn->prepare("SELECT * FROM Product WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $productResult = $stmt->get_result();
        $product = $productResult->fetch_assoc();
        $stmt->close();

        if (!$product) {
            ResponseAPI::error("Product does not exist.", null, 404);
        }

        // Always fetch these for all return types except "Reviews"
        if ($returnType !== 'Reviews') {
            // Get cheapest price and retailer
            $stmt = $conn->prepare("
                SELECT sb.price AS cheapest_price, r.id AS retailer_id, r.name AS retailer_name
                FROM Supplied_By sb
                INNER JOIN Retailer r ON sb.retailer_id = r.id
                WHERE sb.product_id = ?
                ORDER BY sb.price ASC
                LIMIT 1
            ");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stmt->bind_result($cheapest_price, $cheapest_retailer_id, $cheapest_retailer_name);
            if ($stmt->fetch()) {
                $product['cheapest_price'] = $cheapest_price !== null ? (float)number_format($cheapest_price, 2, '.', '') : null;
                $product['cheapest_retailer'] = $cheapest_retailer_name !== null ? $cheapest_retailer_name : null;
                $product['cheapest_retailer_id'] = $cheapest_retailer_id !== null ? (int)$cheapest_retailer_id : null;
            } else {
                $product['cheapest_price'] = null;
                $product['cheapest_retailer'] = null;
                $product['cheapest_retailer_id'] = null;
            }
            $stmt->close();

            // Get average review
            $stmt = $conn->prepare("SELECT ROUND(AVG(score), 1) AS average_review FROM Rating WHERE product_id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $stmt->bind_result($average_review);
            $stmt->fetch();
            $product['average_review'] = $average_review !== null ? (float)$average_review : null;
            $stmt->close();
        }

        // Get all retailers for this product (with sorting)
        if ($returnType === 'All' || $returnType === 'Retailers') {
            $sql = "
                SELECT r.id AS retailer_id, r.name AS retailer_name, sb.price
                FROM Supplied_By sb
                INNER JOIN Retailer r ON sb.retailer_id = r.id
                WHERE sb.product_id = ?
                ORDER BY $retailer_order
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $retailersResult = $stmt->get_result();
            $retailers = [];
            while ($row = $retailersResult->fetch_assoc()) {
                $retailers[] = [
                    'retailer_id' => (int)$row['retailer_id'],
                    'retailer_name' => $row['retailer_name'],
                    'price' => (float)number_format($row['price'], 2, '.', '')
                ];
            }
            $stmt->close();
        }

        // Get all reviews for this product (with sorting and optional score filter)
        if ($returnType === 'All' || $returnType === 'Reviews') {
            $sql = "
                SELECT r.id AS review_id, r.score, r.description, r.updated_at, u.id AS user_id, u.name AS user_name
                FROM Rating r
                INNER JOIN User u ON r.user_id = u.id
                WHERE r.product_id = ?
            ";
            $types = "i";
            $params = [$product_id];
            if ($filter_reviews_by_score !== null) {
                $sql .= " AND r.score = ?";
                $types .= "i";
                $params[] = $filter_reviews_by_score;
            }
            $sql .= " ORDER BY $review_order";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $reviewsResult = $stmt->get_result();
            $reviews = [];
            while ($row = $reviewsResult->fetch_assoc()) {
                $reviews[] = [
                    'review_id' => (int)$row['review_id'],
                    'customer_id' => (int)$row['user_id'],
                    'customer_name' => $row['user_name'],
                    'score' => (int)$row['score'],
                    'description' => $row['description'],
                    'updated_at' => $row['updated_at']
                ];
            }
            $stmt->close();
        }

        // Build response based on "return" parameter
        if ($returnType === 'All') {
            $product['retailers'] = isset($retailers) ? $retailers : [];
            $product['reviews'] = isset($reviews) ? $reviews : [];
            ResponseAPI::send("Product details fetched successfully", $product, 200);
        } elseif ($returnType === 'Product') {
            // Only product info (including cheapest price/retailer and average review)
            unset($product['retailers'], $product['reviews']);
            ResponseAPI::send("Product details fetched successfully", $product, 200);
        } elseif ($returnType === 'Retailers') {
            ResponseAPI::send("Retailers fetched successfully", isset($retailers) ? $retailers : [], 200);
        } elseif ($returnType === 'Reviews') {
            ResponseAPI::send("Reviews fetched successfully", isset($reviews) ? $reviews : [], 200);
        }
    }// getProductDetails

    public static function addTowatchlist($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        if (empty($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            ResponseAPI::error("Product ID is required and must be an integer.", null, 422);
        }
        $product_id = (int)$requestData['product_id'];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get customer id
        $stmt = $conn->prepare("SELECT id FROM User WHERE apikey = ?");
        $stmt->bind_param("s", $requestData['apikey']);
        $stmt->execute();
        $stmt->bind_result($cust_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User not found.", null, 404);
        }
        $stmt->close();

        // Check if product exists
        $stmt = $conn->prepare("SELECT id FROM Product WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Product does not exist.", null, 404);
        }
        $stmt->close();

        // Check if already in watchlist
        $stmt = $conn->prepare("SELECT * FROM watchlist WHERE cust_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $cust_id, $product_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            ResponseAPI::send("Product already in watchlist", ['product_id' => $product_id], 200);
        }
        $stmt->close();

        // Add to watchlist
        $stmt = $conn->prepare("INSERT INTO watchlist (cust_id, product_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $cust_id, $product_id);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to add to watchlist: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Product added to watchlist successfully", ['product_id' => $product_id], 201);
    }//addTowatchlist

    public static function removeFromwatchlist($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        if (empty($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            ResponseAPI::error("Product ID is required and must be an integer.", null, 422);
        }
        $product_id = (int)$requestData['product_id'];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get customer id
        $stmt = $conn->prepare("SELECT id FROM User WHERE apikey = ?");
        $stmt->bind_param("s", $requestData['apikey']);
        $stmt->execute();
        $stmt->bind_result($cust_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User not found.", null, 404);
        }
        $stmt->close();

        // Check if product exists
        $stmt = $conn->prepare("SELECT id FROM Product WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Product does not exist.", null, 404);
        }
        $stmt->close();

        // Remove from watchlist
        $stmt = $conn->prepare("DELETE FROM watchlist WHERE cust_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $cust_id, $product_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 0) {
            ResponseAPI::send("Product not in watchlist", ['product_id' => $product_id], 200);
        }

        ResponseAPI::send("Product removed from watchlist successfully", ['product_id' => $product_id], 200);
    }//removeFromwatchlist

    public static function getMywatchlist($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        Authorise::authenticate($requestData['apikey'], 'Customer');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get customer id
        $stmt = $conn->prepare("SELECT id FROM User WHERE apikey = ?");
        $stmt->bind_param("s", $requestData['apikey']);
        $stmt->execute();
        $stmt->bind_result($cust_id);
        if (!$stmt->fetch()) {
            $stmt->close();
            ResponseAPI::error("User not found.", null, 404);
        }
        $stmt->close();

        // Get all products in watchlist with product details (same as getAllProducts for customer)
        $sql = "
            SELECT 
                p.id AS product_id,
                p.name AS title,
                p.image_url,
                p.category,
                ROUND(AVG(r.score), 1) AS average_rating,
                sp.cheapest_price,
                sp.retailer_id,
                rt.name AS retailer_name
            FROM watchlist w
            INNER JOIN Product p ON w.product_id = p.id
            LEFT JOIN Rating r ON r.product_id = p.id
            LEFT JOIN (
                SELECT sb.product_id, sb.retailer_id, sb.price AS cheapest_price
                FROM Supplied_By sb
                INNER JOIN (
                    SELECT product_id, MIN(price) AS min_price
                    FROM Supplied_By
                    GROUP BY product_id
                ) minp ON sb.product_id = minp.product_id AND sb.price = minp.min_price
            ) sp ON sp.product_id = p.id
            LEFT JOIN Retailer rt ON sp.retailer_id = rt.id
            WHERE w.cust_id = ?
            GROUP BY p.id
            ORDER BY p.name ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'product_id' => (int)$row['product_id'],
                'title' => $row['title'],
                'image_url' => $row['image_url'],
                'category' => $row['category'],
                'average_rating' => $row['average_rating'] !== null ? (float)number_format($row['average_rating'], 1) : null,
                'cheapest_price' => $row['cheapest_price'] !== null ? (float)number_format($row['cheapest_price'], 2, '.', '') : null,
                'retailer_id' => $row['retailer_id'] !== null ? (int)$row['retailer_id'] : null,
                'retailer_name' => $row['retailer_name'] !== null ? $row['retailer_name'] : null
            ];
        }
        $stmt->close();

        ResponseAPI::send("watchlist fetched successfully", $products, 200);
    }//getMywatchlist

    public static function getReviewStats($requestData) {
        if (empty($requestData['apikey'])) {
            ResponseAPI::error("API key is required to authenticate user", null, 401);
        }
        // Allow both Admin and Customer to view stats
        Authorise::authenticate($requestData['apikey'], 'Both');

        $db = Database::getInstance();
        $conn = $db->getConnection();

        $returnType = isset($requestData['return']) ? $requestData['return'] : 'All';

        // Initialize all variables
        $starCounts = [];
        $starAverage_Counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $totalReviews = 0;
        $piePercentages = [];
        $avgReviewScore = null;
        $bestProducts = [];
        $worstProducts = [];
        $mostReviewed = [];
        $leastReviewed = [];
        $mostActiveReviewers = [];
        $recentReviews = [];
        $reviewGrowth = [];
        $productsNoReviews = [];
        $reviewLengthStats = [];
        $avgScorePerCategory = [];
        $percentProductsWithReviews = null;
        $topRatedWithMinReviews = [];

        // 1. Count of reviews for each star rating (1-5)
        if ($returnType === 'All' || $returnType === 'star_counts') {
            $sql = "SELECT score, COUNT(*) as count FROM Rating GROUP BY score";
            $result = $conn->query($sql);
            $starCounts = array_fill(1, 5, 0);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    if ($row && isset($row['score'])) {
                        $starCounts[(int)$row['score']] = (int)$row['count'];
                    }
                }
            }
        }

        // 1b. Count of products by average review (binned, 1.0-1.9, 2.0-2.9, ..., 5.0)
        if ($returnType === 'All' || $returnType === 'starAverage_Counts' || $returnType === 'pie_percentages') {
        $sql = "
            SELECT 
                ROUND(AVG(r.score), 1) AS avg_rating
            FROM Product p
            JOIN Rating r ON r.product_id = p.id
            GROUP BY p.id
        ";
        $result = $conn->query($sql);
        $starAverage_Counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                if ($row && isset($row['avg_rating']) && $row['avg_rating'] !== null) {
                    $avg = (float)$row['avg_rating'];
                    if ($avg >= 1.0 && $avg < 2.0) $starAverage_Counts[1]++;
                    else if ($avg >= 2.0 && $avg < 3.0) $starAverage_Counts[2]++;
                    else if ($avg >= 3.0 && $avg < 4.0) $starAverage_Counts[3]++;
                    else if ($avg >= 4.0 && $avg < 5.0) $starAverage_Counts[4]++;
                    else if ($avg == 5.0) $starAverage_Counts[5]++;
                }
            }
        }
    }

        // 2. Total number of reviews
        if ($returnType === 'All' || $returnType === 'total_reviews') {
            $sql = "SELECT COUNT(*) as total FROM Rating";
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_assoc()) && isset($row['total'])) {
                $totalReviews = (int)$row['total'];
            } else {
                $totalReviews = 0;
            }
        }

        // 3. Pie chart percentages for each star rating (1-5)
        if ($returnType === 'All' || $returnType === 'pie_percentages') {
        $totalProductsWithReviews = array_sum($starAverage_Counts);
        if ($totalProductsWithReviews > 0) {
            foreach ($starAverage_Counts as $star => $count) {
                $piePercentages[$star] = round(($count / $totalProductsWithReviews) * 100, 1);
            }
        }
    }

        // 4. Average review rating across all products (float, using average of product averages)
        if ($returnType === 'All' || $returnType === 'average_review') {
            $sql = "
                SELECT AVG(avg_rating) as avg_score
                FROM (
                    SELECT ROUND(AVG(r.score), 1) as avg_rating
                    FROM Product p
                    JOIN Rating r ON r.product_id = p.id
                    GROUP BY p.id
                ) as product_averages
            ";
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_assoc()) && isset($row['avg_score'])) {
                $avgReviewScore = $row['avg_score'] !== null ? (float)$row['avg_score'] : null;
            } else {
                $avgReviewScore = null;
            }
        }

        // 5. Best and worst review(s) star rating only: According to the average rating of each product
        if ($returnType === 'All' || $returnType === 'best_worst_products') {
            // Best
            $sql = "
                SELECT p.id, p.name, p.image_url, p.category, ROUND(AVG(r.score), 1) as avg_score, COUNT(r.id) as review_count
                FROM Product p
                JOIN Rating r ON r.product_id = p.id
                GROUP BY p.id
                HAVING review_count > 0
                ORDER BY avg_score DESC
                LIMIT 1
            ";
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_assoc())) {
                $bestProducts[] = [
                    'product_id' => (int)$row['id'],
                    'title' => $row['name'],
                    'image_url' => $row['image_url'],
                    'category' => $row['category'],
                    'average_rating' => round($row['avg_score'], 1),
                    'review_count' => (int)$row['review_count']
                ];
            }
            // Worst
            $sql = "
                SELECT p.id, p.name, p.image_url, p.category, ROUND(AVG(r.score), 1) as avg_score, COUNT(r.id) as review_count
                FROM Product p
                JOIN Rating r ON r.product_id = p.id
                GROUP BY p.id
                HAVING review_count > 0
                ORDER BY avg_score ASC
                LIMIT 1
            ";
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_assoc())) {
                $worstProducts[] = [
                    'product_id' => (int)$row['id'],
                    'title' => $row['name'],
                    'image_url' => $row['image_url'],
                    'category' => $row['category'],
                    'average_rating' => round($row['avg_score'], 1),
                    'review_count' => (int)$row['review_count']
                ];
            }
        }

        // 6. Most Reviewed Product(s)
        if ($returnType === 'All' || $returnType === 'most_reviewed_products') {
            $sql = "
                SELECT p.id, p.name, p.image_url, p.category, COUNT(r.id) as review_count
                FROM Product p
                JOIN Rating r ON r.product_id = p.id
                GROUP BY p.id
                ORDER BY review_count DESC
                LIMIT 1
            ";
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_assoc())) {
                $mostReviewed[] = [
                    'product_id' => (int)$row['id'],
                    'title' => $row['name'],
                    'image_url' => $row['image_url'],
                    'category' => $row['category'],
                    'review_count' => (int)$row['review_count']
                ];
            }
        }

        // 7. Least Reviewed Product(s) (but >0)
        if ($returnType === 'All' || $returnType === 'least_reviewed_products') {
            $sql = "
                SELECT p.id, p.name, p.image_url, p.category, COUNT(r.id) as review_count
                FROM Product p
                JOIN Rating r ON r.product_id = p.id
                GROUP BY p.id
                HAVING review_count > 0
                ORDER BY review_count ASC
                LIMIT 1
            ";
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_assoc())) {
                $leastReviewed[] = [
                    'product_id' => (int)$row['id'],
                    'title' => $row['name'],
                    'image_url' => $row['image_url'],
                    'category' => $row['category'],
                    'review_count' => (int)$row['review_count']
                ];
            }
        }

        // 8. Most Active Reviewer(s)
        if ($returnType === 'All' || $returnType === 'most_active_reviewers') {
            $sql = "
                SELECT u.id, u.name, COUNT(r.id) as review_count
                FROM User u
                JOIN Rating r ON r.user_id = u.id
                GROUP BY u.id
                ORDER BY review_count DESC
                LIMIT 1
            ";
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_assoc())) {
                $mostActiveReviewers[] = [
                    'user_id' => (int)$row['id'],
                    'name' => $row['name'],
                    'review_count' => (int)$row['review_count']
                ];
            }
        }

        // 9. Recent Reviews (last 5)
        if ($returnType === 'All' || $returnType === 'recent_reviews') {
            $sql = "
                SELECT r.id AS review_id, r.score, r.description, r.updated_at, u.id AS user_id, u.name AS user_name, p.id AS product_id, p.name AS product_name
                FROM Rating r
                INNER JOIN User u ON r.user_id = u.id
                INNER JOIN Product p ON r.product_id = p.id
                ORDER BY r.updated_at DESC
                LIMIT 5
            ";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    if ($row) {
                        $recentReviews[] = [
                            'review_id' => (int)$row['review_id'],
                            'score' => (int)$row['score'],
                            'description' => $row['description'],
                            'updated_at' => $row['updated_at'],
                            'user_id' => (int)$row['user_id'],
                            'user_name' => $row['user_name'],
                            'product_id' => (int)$row['product_id'],
                            'product_name' => $row['product_name']
                        ];
                    }
                }
            }
        }

        // 10. Review Growth Over Time (last 12 months)
        if ($returnType === 'All' || $returnType === 'review_growth') {
            $sql = "
                SELECT DATE_FORMAT(updated_at, '%Y-%m') AS month, COUNT(*) AS review_count
                FROM Rating
                WHERE updated_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY month
                ORDER BY month ASC
            ";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    if ($row) {
                        $reviewGrowth[] = [
                            'month' => $row['month'],
                            'review_count' => (int)$row['review_count']
                        ];
                    }
                }
            }
        }

        // 11. Products With No Reviews
        if ($returnType === 'All' || $returnType === 'products_no_reviews') {
        $sql = "
            SELECT COUNT(*) AS count
            FROM Product p
            LEFT JOIN Rating r ON r.product_id = p.id
            WHERE r.id IS NULL
        ";
        $result = $conn->query($sql);
        if ($result && ($row = $result->fetch_assoc()) && isset($row['count'])) {
            $productsNoReviews = (int)$row['count'];
        } else {
            $productsNoReviews = 0;
        }
    }

        // 12. Distribution of Review Lengths
        if ($returnType === 'All' || $returnType === 'review_length_stats') {
            $sql = "
                SELECT 
                    AVG(CHAR_LENGTH(description)) AS avg_length,
                    MIN(CHAR_LENGTH(description)) AS min_length,
                    MAX(CHAR_LENGTH(description)) AS max_length
                FROM Rating
            ";
            $result = $conn->query($sql);
            $row = $result && ($tmp = $result->fetch_assoc()) ? $tmp : null;
            $reviewLengthStats = [
                'average_length' => $row && $row['avg_length'] !== null ? round($row['avg_length'], 1) : null,
                'min_length' => $row && $row['min_length'] !== null ? (int)$row['min_length'] : null,
                'max_length' => $row && $row['max_length'] !== null ? (int)$row['max_length'] : null
            ];
            // Median
            $sql = "SELECT CHAR_LENGTH(description) AS len FROM Rating ORDER BY len";
            $result = $conn->query($sql);
            $lengths = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    if ($row && isset($row['len'])) {
                        $lengths[] = (int)$row['len'];
                    }
                }
            }
            $count = count($lengths);
            if ($count > 0) {
                sort($lengths);
                $mid = (int)($count / 2);
                $reviewLengthStats['median_length'] = $count % 2 === 0 ? ($lengths[$mid - 1] + $lengths[$mid]) / 2 : $lengths[$mid];
            } else {
                $reviewLengthStats['median_length'] = null;
            }
        }

        // 13. Average Review Score Per Category (rounded to 1 decimal)
        if ($returnType === 'All' || $returnType === 'avg_score_per_category') {
            $sql = "
                SELECT p.category, ROUND(AVG(r.score), 1) AS avg_score
                FROM Product p
                JOIN Rating r ON r.product_id = p.id
                WHERE p.category IS NOT NULL AND p.category != ''
                GROUP BY p.category
            ";
            $result = $conn->query($sql);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    if ($row) {
                        $avgScorePerCategory[] = [
                            'category' => $row['category'],
                            'average_score' => $row['avg_score'] !== null ? (float)$row['avg_score'] : null
                        ];
                    }
                }
            }
        }

        // 14. Percentage of Products With At Least One Review
        if ($returnType === 'All' || $returnType === 'percent_products_with_reviews') {
            $sql = "SELECT COUNT(*) AS total FROM Product";
            $result = $conn->query($sql);
            $totalProducts = ($result && ($row = $result->fetch_assoc()) && isset($row['total'])) ? (int)$row['total'] : 0;
            $sql = "SELECT COUNT(DISTINCT product_id) AS reviewed FROM Rating";
            $result = $conn->query($sql);
            $reviewedProducts = ($result && ($row = $result->fetch_assoc()) && isset($row['reviewed'])) ? (int)$row['reviewed'] : 0;
            $percentProductsWithReviews = $totalProducts > 0 ? round(($reviewedProducts / $totalProducts) * 100, 1) : 0.0;
        }

        // 15. Top Rated Product(s) With At Least X Reviews (average rounded to 1 decimal)
        if ($returnType === 'All' || $returnType === 'top_rated_with_min_reviews') {
            $min_reviews = isset($requestData['min_reviews']) && is_numeric($requestData['min_reviews']) ? (int)$requestData['min_reviews'] : 2;
            $sql = "
                SELECT p.id, p.name, p.image_url, p.category, ROUND(AVG(r.score), 1) as avg_score, COUNT(r.id) as review_count
                FROM Product p
                JOIN Rating r ON r.product_id = p.id
                GROUP BY p.id
                HAVING review_count >= ?
                ORDER BY avg_score DESC
                LIMIT 1
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $min_reviews);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && ($row = $result->fetch_assoc())) {
                $topRatedWithMinReviews[] = [
                    'product_id' => (int)$row['id'],
                    'title' => $row['name'],
                    'image_url' => $row['image_url'],
                    'category' => $row['category'],
                    'average_rating' => round($row['avg_score'], 1),
                    'review_count' => (int)$row['review_count']
                ];
            }
            $stmt->close();
        }

        // 16. Total number of products in the database
        if ($returnType === 'All' || $returnType === 'number_of_products') {
            $sql = "SELECT COUNT(*) AS total FROM Product";
            $result = $conn->query($sql);
            if ($result && ($row = $result->fetch_assoc()) && isset($row['total'])) {
                $numberOfProducts = (int)$row['total'];
            } else {
                $numberOfProducts = 0;
            }
        }

        // Build response
        $data = [];
        if ($returnType === 'All' || $returnType === 'star_counts') $data['star_counts'] = $starCounts;
        if ($returnType === 'All' || $returnType === 'starAverage_Counts') $data['starAverage_Counts'] = $starAverage_Counts;
        if ($returnType === 'All' || $returnType === 'total_reviews') $data['total_reviews'] = $totalReviews;
        if ($returnType === 'All' || $returnType === 'pie_percentages') $data['pie_percentages'] = $piePercentages;
        if ($returnType === 'All' || $returnType === 'average_review') $data['average_review'] = $avgReviewScore;
        if ($returnType === 'All' || $returnType === 'best_worst_products') {
            $data['best_products'] = $bestProducts;
            $data['worst_products'] = $worstProducts;
        }
        if ($returnType === 'All' || $returnType === 'most_reviewed_products') $data['most_reviewed_products'] = $mostReviewed;
        if ($returnType === 'All' || $returnType === 'least_reviewed_products') $data['least_reviewed_products'] = $leastReviewed;
        if ($returnType === 'All' || $returnType === 'most_active_reviewers') $data['most_active_reviewers'] = $mostActiveReviewers;
        if ($returnType === 'All' || $returnType === 'review_growth') $data['review_growth'] = $reviewGrowth;
        if ($returnType === 'All' || $returnType === 'products_no_reviews') $data['products_no_reviews'] = $productsNoReviews;
        if ($returnType === 'All' || $returnType === 'number_of_products') $data['number_of_products'] = $numberOfProducts;
        if ($returnType === 'All' || $returnType === 'review_length_stats') $data['review_length_stats'] = $reviewLengthStats;
        if ($returnType === 'All' || $returnType === 'avg_score_per_category') $data['avg_score_per_category'] = $avgScorePerCategory;
        if ($returnType === 'All' || $returnType === 'percent_products_with_reviews') $data['percent_products_with_reviews'] = $percentProductsWithReviews;
        if ($returnType === 'All' || $returnType === 'top_rated_with_min_reviews') $data['top_rated_with_min_reviews'] = $topRatedWithMinReviews;

        ResponseAPI::send("Review statistics fetched successfully", $data, 200);
    }

}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);

    if (empty($requestData) || !is_array($requestData)) {
        ResponseAPI::error("Invalid request data: Is your request a valid JSON object?", null, 400); //400 Bad Request
    }

    if (!isset($requestData['type'])) {
        ResponseAPI::error("Request type is required", null, 400); //400 Bad Request
    }

    try {
        $db = Database::getInstance();
        
        switch ($requestData['type']) {
            case 'Test':
                Tester::handleTest($requestData);
                break;

            //ADMIN
            case 'Register':
                USER::register($requestData);
                break;

            case 'Login':
                USER::login($requestData);
                break;

            case 'QuickAddUser':
                ADMIN::QuickAddUser($requestData);
                break;

            case 'QuickEditProductPrice':
                ADMIN::QuickEditProductPrice($requestData);
                break;

            case 'AdminRecentReviews':
                ADMIN::AdminRecentReviews($requestData);
                break;

            case 'AddNewProduct':
                ADMIN::AddNewProduct($requestData);
                break;

            case 'deleteProduct':
                ADMIN::deleteProduct($requestData);
                break;

            case 'GetAllRetailers':
                ADMIN::GetAllRetailers($requestData);
                break;

            case 'AddRetailer':
                ADMIN::AddRetailer($requestData);
                break;

            case 'EditRetailer':
                ADMIN::EditRetailer($requestData);
                break;

            case 'getAllUsers':
                ADMIN::getAllUsers($requestData);
                break;

            case 'AddNewStaff':
                ADMIN::AddNewStaff($requestData);
                break;
            
            case 'editUser':
                ADMIN::editUser($requestData);
                break;

            case 'deleteUser':
                ADMIN::deleteUser($requestData);
                break;

            case 'deleteRating':
                ADMIN::deleteRating($requestData);
                break;

            case 'deleteRetailer':
                ADMIN::deleteRetailer($requestData);
                break;

            case 'editProduct':
                ADMIN::editProduct($requestData);
                break;

            //BOTH
            case 'getAllProducts':
            // Check user type
            if (empty($requestData['apikey'])) {
                ResponseAPI::error("API key is required to authenticate user", null, 401);
            }
            // Get user type from API key
            $db = Database::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare('SELECT user_type FROM User WHERE apikey=?');
            $stmt->bind_param('s', $requestData['apikey']);
            $stmt->execute();
            $stmt->bind_result($user_type);
            if (!$stmt->fetch()) {
                $stmt->close();
                ResponseAPI::error("Invalid API key or User not found", null, 401);
            }
            $stmt->close();

            if (strcasecmp($user_type, 'Admin') === 0) {
                ADMIN::getAllProducts($requestData);
            } else if (strcasecmp($user_type, 'Customer') === 0) {
                CUSTOMER::getAllProducts($requestData);
            } else {
                ResponseAPI::error("Unknown user type", null, 403); //403 Forbidden
            }
            break;

            //CUSTOMER
            case 'getAllCategories':
                CUSTOMER::getAllCategories($requestData);
                break;

            case 'getMyDetails':
                CUSTOMER::getMyDetails($requestData);
                break;

            case 'updateMyDetails':
                CUSTOMER::updateMyDetails($requestData);
                break;

            case 'getMyReviews':
                CUSTOMER::getMyReviews($requestData);
                break;

            case 'writeReview':
                CUSTOMER::writeReview($requestData);
                break;
            
            case 'editMyReview':
                CUSTOMER::editMyReview($requestData);
                break;

            case 'deleteMyReview':
                CUSTOMER::deleteMyReview($requestData);
                break;            
                
            case 'getProductDetails':
                CUSTOMER::getProductDetails($requestData);
                break;

            case 'addToWatchlist':
                CUSTOMER::addTowatchlist($requestData);
                break;

            case 'removeFromWatchlist':
                CUSTOMER::removeFromwatchlist($requestData);
                break;

            case 'getMyWatchlist':
                CUSTOMER::getMywatchlist($requestData);
                break;

            case 'getReviewStats':
                CUSTOMER::getReviewStats($requestData);
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
?>