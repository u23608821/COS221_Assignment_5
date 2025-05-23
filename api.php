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

        // Validate input
        if (empty($requestData['user_id']) || !is_numeric($requestData['user_id'])) {
            ResponseAPI::error("User ID is required and must be an integer.", null, 422);
        }
        if (empty($requestData['product_id']) || !is_numeric($requestData['product_id'])) {
            ResponseAPI::error("Product ID is required and must be an integer.", null, 422);
        }
        $user_id = (int)$requestData['user_id'];
        $product_id = (int)$requestData['product_id'];

        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if user exists
        $stmt = $conn->prepare("SELECT id FROM User WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("User does not exist.", null, 404);
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

        // Check if rating exists
        $stmt = $conn->prepare("SELECT * FROM Rating WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 0) {
            $stmt->close();
            ResponseAPI::error("Rating does not exist for this user and product.", null, 404);
        }
        $stmt->close();

        // Delete the rating
        $stmt = $conn->prepare("DELETE FROM Rating WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        if (!$stmt->execute()) {
            $stmt->close();
            ResponseAPI::error("Failed to delete rating: " . $conn->error, ['database_error' => $conn->error], 500);
        }
        $stmt->close();

        ResponseAPI::send("Rating deleted successfully", ['user_id' => $user_id, 'product_id' => $product_id], 200);
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

        // Build base query
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
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);

    if (empty($requestData) || !is_array($requestData)) {
        ResponseAPI::error("Invalid request data: Is your request object valid JSON?", null, 400);
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