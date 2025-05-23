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

    public function TestResponse($requestData)
    {
        try {
            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, (int) $this->dbPort);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $hello = isset($requestData['hello']) ? trim($requestData['hello']) : '';
            $world = isset($requestData['world']) ? trim($requestData['world']) : '';

            if (empty($hello) || empty($world)) {
                $this->sendResponse('error', "missing required fields");
            }

            file_put_contents("debug.json", json_encode($requestData, JSON_PRETTY_PRINT));



            $this->sendResponse("Success", "Hello world back to you!");
            $conn->close();
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }

    }

    public function Register($requestData)
    {

        try {
            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $name = isset($requestData["name"]) ? trim($requestData["name"]) : "";
            $surname = isset($requestData["surname"]) ? trim($requestData["surname"]) : "";
            $phone_number = isset($requestData["phone_number"]) ? trim($requestData["phone_number"]) : "";
            $email = isset($requestData["email"]) ? trim($requestData["email"]) : "";
            $password = isset($requestData["password"]) ? trim($requestData["password"]) : "";
            $street_number = isset($requestData["street_number"]) ? trim($requestData["street_number"]) : "";
            $street_name = isset($requestData["street_name"]) ? trim($requestData["street_name"]) : "";
            $suburb = isset($requestData["suburb"]) ? trim($requestData["suburb"]) : "";
            $city = isset($requestData["city"]) ? trim($requestData["city"]) : "";
            $zip_code = isset($requestData["zip_code"]) ? trim($requestData["zip_code"]) : "";
            $user_type = isset($requestData["user_type"]) ? trim($requestData["user_type"]) : "";



            //must have unique email
            if (empty($name) || empty($surname) || empty($phone_number) || empty($email) || empty($password)) {
                $this->sendResponse('error', 'Missing required fields');
            } elseif (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/', $email)) {
                $this->sendResponse('emailError', 'Invalid email address');
            } elseif (!preg_match("/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/", $password)) {
                $this->sendResponse('passwordError', 'Password does not meet requirements');
            } else {
                //check if email is unique
                $emailcheck = $conn->prepare("SELECT * FROM User WHERE email = ?");
                $emailcheck->bind_param("s", $email);
                $emailcheck->execute();

                if ($emailcheck->get_result()->fetch_assoc()) {
                    $this->sendResponse("error", "Email already exists");
                }

                //All good so we can insert the user
                //set api key
                $apikey = base64_encode(random_bytes(32));
                $password = password_hash($password, PASSWORD_DEFAULT);
                $sqlInsert = $conn->prepare("INSERT INTO User(name,surname, phone_number,apikey, email, password, street_number, street_name, suburb,city,zip_code, user_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
                $sqlInsert->bind_param("ssssssssssss", $name, $surname, $phone_number, $apikey, $email, $password, $street_number, $street_name, $suburb, $city, $zip_code, $user_type);

                if ($sqlInsert->execute()) {
                    $user_id = mysqli_insert_id($conn);
                    if ($user_type === "Customer") {
                        $pfp = isset($requestData["profile_picture"]) ? trim($requestData["profile_picture"]) : "";
                        $insertC = $conn->prepare("INSERT INTO Customer(user_id, profile_picture) VALUES (?,?)");
                        $insertC->bind_param("is", $user_id, $pfp);
                        $insertC->execute();
                        $insertC->close();
                    } else if ($user_type === "Admin") {
                        $salary = isset($requestData["salary"]) ? trim($requestData["salary"]) : "";
                        $position = isset($requestData["position"]) ? trim($requestData["position"]) : "";
                        $insertA = $conn->prepare("INSERT INTO Admin(user_id, salary, position) VALUES (?,?,?)");
                        $insertA->bind_param("ids", $user_id, $salary, $position);
                        $insertA->execute();
                        $insertA->close();
                    } else {
                        $this->sendResponse("error", "user type unrecognized");
                        return;
                    }
                } else {
                    $this->sendResponse("error", "User registration failed");
                }

                $sqlInsert->close();
                $conn->close();


            }

            $this->sendResponse("success", "Inserted new user");



        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            $this->sendResponse("error", $e->getMessage());
            exit;
        }

    }

    public function Login($requestData)
    {
        try {
            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $email = isset($requestData["email"]) ? trim($requestData["email"]) : "";
            $password = isset($requestData["password"]) ? trim($requestData["password"]) : "";

            if (empty($email) || empty($password)) {
                $this->sendResponse("error", "All fields must be valid");
            }


            $stmt = $conn->prepare("SELECT * FROM User WHERE email=?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result()->fetch_assoc();
            //echo("Email: ".$result["email"] );
            //echo("Password: ".$result["password"] );
            if (password_verify($password, $result["password"])) {
                $cookie_email = $result["email"];
                $cookie_name = $result["name"]; //To use when displaying the users profile
                $cookie_surname = $result["surname"];
                $cookie_key = $result["apikey"];

                setcookie("userapikey", $cookie_key, time() + (259200 * 30), "/"); //set for 3 days
                setcookie("useremail", $cookie_email, time() + (259200 * 30), "/"); //set for 3 days
                setcookie("username", $cookie_name, time() + (259200 * 30), "/"); //set for 3 days
                setcookie("usersurname", $cookie_surname, time() + (259200 * 30), "/"); //set for 3 days


                $this->sendResponse(
                    "success"
                    ,
                    [
                        'apikey' => $result["apikey"]
                        ,
                        'username' => $result["name"]
                        ,
                        'surname' => $result["surname"]
                        ,
                        'email' => $result["email"]
                    ]
                );
            } else {
                $this->sendResponse("error", "Unknown email or password");
            }


            $stmt->close();
            $conn->close();

        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }





    }
    public function ViewAllProducts($requestData)
    {

        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $stmt = $conn->prepare("SELECT * FROM Product");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            //$data = $result->fetch_all(MYSQLI_ASSOC);
            if (!empty($result)) {
                $this->sendResponse("success", $result);
            }
            $stmt->close();
            $conn->close();
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }

    }
    public function RateProduct($requestData)
    {

        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $productid = isset($requestData["product_id"]) ? $requestData["product_id"] :"";
            $score = isset($requestData["score"]) ? $requestData["score"] : 5;
            $description = isset($requestData["description"]) ? $requestData["description"] :"";
            $userid = isset($requestData["user_id"]) ? $requestData["user_id"] :"";

            $sqlInsert = $conn->prepare("INSERT INTO Rating(score, description, user_id, product_id) VALUES (?,?,?,?)");
            $sqlInsert->bind_param("isii", $score, $description,$userid, $productid);
            $sqlInsert->execute();
            $result = $sqlInsert->get_result();
            if(!empty($result)){
                $this->sendResponse("success", "Inserted rating");
            }

            $conn->close();
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }
    }
    public function AddProduct($requestData)
    {
        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }
    }
    public function UpdateProduct($requestData)
    {

        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }
    }
    public function DeleteProduct($requestData)
    {

        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }
    }
    public function ViewRatings($requestData)
    {
        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $productid = isset($requestData["product_id"]) ? $requestData["product_id"] : 0;
            $stmt = $conn->prepare("SELECT * FROM Rating WHERE product_id=?");
            $stmt->bind_param("i", $productid);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            if(!empty($result)) {
                $this->sendResponse("success", $result);
            }
            $stmt->close();
            $conn->close();
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }
    }
    public function FilterProducts($requestData)
    {
        //filter based on whatever
        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }
    }

    public function UpdateAdmin($requestData)
    {
        //filter based on whatever
        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            $fields = [];
            $adminFields =[];
            $values = [];
            $adminValues = [];
            $types = "";
            $adminTypes = "";
            $fieldMap = [
                "name" => "s",
                "surname" => "s",
                "phone_number" => "s",
                "apikey" => "s",
                "email" => "s",
                "password" => "s",
                "street_number" => "s",
                "street_name" => "s",
                "suburb" => "s",
                "city" => "s",
                "zip_code" => "s",
                "user_type" => "s"
            ];
            $adminFieldMap = [

                "salary" => "d",
                "position" => "s"
            ];

            foreach ($fieldMap as $key => $type) {
                if (isset($requestData[$key])) {
                    $fields[] = "$key = ?";
                    $values[] = $requestData[$key];
                    $types .= $type;
                }
            }

            foreach ($adminFieldMap as $key => $type) {
                if (isset($requestData[$key])) {
                    $adminFields[] = "$key = ?";
                    $adminValues[] = $requestData[$key];
                    $adminTypes .= $type;
                }
            }

            if(count($fields) > 0 && isset($fieldMap['id']))
            {

            $conn->begin_transaction();

            // Update User table
            $userSql = "UPDATE User SET ". implode(", ", $fields) . "WHERE id=?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param($types, $values);
            $userStmt->execute();

            // Update Admin table
            $adminSql = "UPDATE Admin SET " . implode(", ", $adminFields) ." WHERE user_id=?";
            $adminStmt = $conn->prepare($adminSql);
            $adminStmt->bind_param($adminTypes, $adminValues, $fieldMap['id']); // d for double (salary), s for string, i for int
            $adminStmt->execute();

            $conn->commit();

            $conn->close();

            }
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            } 
            $this->sendResponse("error", $e->getMessage());
            echo "Update failed: " . $e->getMessage();
        } finally {
            if (isset($userStmt))
                $userStmt->close();
            if (isset($adminStmt))
                $adminStmt->close();
            if (isset($conn))
                $conn->close();
        }



    }
    public function UpdateCustomer($requestData)
    {
        //filter based on whatever
        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }


            $fields = [];
            $cusFields =[];
            $values = [];
            $custValues = [];
            $types = "";
            $custTypes = "";
            $fieldMap = [
                "name" => "s",
                "surname" => "s",
                "phone_number" => "s",
                "apikey" => "s",
                "email" => "s",
                "password" => "s",
                "street_number" => "s",
                "street_name" => "s",
                "suburb" => "s",
                "city" => "s",
                "zip_code" => "s",
                "user_type" => "s"
            ];
            $cusFieldMap = [

                "profile_picture" => "s"
            ];

            foreach ($fieldMap as $key => $type) {
                if (isset($requestData[$key])) {
                    $fields[] = "$key = ?";
                    $values[] = $requestData[$key];
                    $types .= $type;
                }
            }

            foreach ($cusFieldMap as $key => $type) {
                if (isset($requestData[$key])) {
                    $cusFields[] = "$key = ?";
                    $custValues[] = $requestData[$key];
                    $custTypes .= $type;
                }
            }

            if(count($fields) > 0 && isset($fieldMap['id']))
            {

            $conn->begin_transaction();

            // Update User table
            $userSql = "UPDATE User SET ". implode(", ", $fields) . "WHERE id=?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param($types, $values);
            $userStmt->execute();

            // Update Admin table
            $adminSql = "UPDATE Admin SET " . implode(", ", $cusFields) ." WHERE user_id=?";
            $adminStmt = $conn->prepare($adminSql);
            $adminStmt->bind_param($custTypes, $custValues, $fieldMap['id']); // d for double (salary), s for string, i for int
            $adminStmt->execute();

            $conn->commit();

            $conn->close();

            }
        } catch (Exception $e) {
            if (isset($conn)) {
                $conn->rollback();
            }
                        $this->sendResponse("error", $e->getMessage());

            echo "Update failed: " . $e->getMessage();
        } finally {
            if (isset($userStmt))
                $userStmt->close();
            if (isset($cusStmt))
                $cusStmt->close();
            if (isset($conn))
                $conn->close();
        }



    }
    public function ViewSupplier($requestData)
    {
        //filter based on whatever
        try {

            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }

        $productid = isset($requestData["id"]) ? $requestData["id"] : "";
        $retailid = isset($requestData["retailer_id"]) ? $requestData["retailer_id"] : "";

        $stmt = $conn->prepare("SELECT * FROM Retailer WHERE id = ?");
        $stmt->bind_param("i", $retailid);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);        
        if (!empty($result)) {
            $this->sendResponse("success",  $result);
        }
        $this->sendResponse("error", "No results found");

        $stmt->close();
        $conn->close();
    }








    ///////////

}


$api = new API();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);

    if (isset($requestData['type'])) {
        //api key checks should be done in function so that it can be tested if admins/customers/all can do it
        if ($requestData['type'] === "Test") { //done
            $api->TestResponse($requestData);
        } else if ($requestData['type'] === "Login") { //50% done
            $api->Login($requestData);
        } else if ($requestData['type'] === "Register") { //50% done
            $api->Register($requestData);
        } else if ($requestData['type'] === "ViewAllProducts") { //done
            $api->ViewAllProducts($requestData);
        } else if ($requestData['type'] === "RateProduct") { //done
            $api->RateProduct($requestData);
        } else if ($requestData['type'] === "AddProduct") {
            $api->AddProduct($requestData);
        } else if ($requestData['type'] === "UpdateProduct") {
            $api->UpdateProduct($requestData);
        } else if ($requestData['type'] === "DeleteProduct") {
            $api->DeleteProduct($requestData);
        } else if ($requestData['type'] === "ViewRatings") { //done
            $api->ViewRatings($requestData);
        } else if ($requestData['type'] === "FilterProducts") {
            $api->FilterProducts($requestData);
        } else if ($requestData['type'] === "UpdateCustomer") { //50% done
            $api->UpdateCustomer($requestData);
        } else if ($requestData['type'] === "UpdateAdmin") { //50% done
            $api->UpdateAdmin($requestData);
        } else if ($requestData['type'] === "ViewSupplier") { //done
            $api->ViewSupplier($requestData);
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




?>