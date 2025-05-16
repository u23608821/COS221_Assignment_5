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

function loadEnv($path)
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



class API
{
    // Access the environment variables

    private $dbHost;
    private $dbName;
    private $dbUser;
    private $dbPassword;
    private $dbPort;

    private $conn;

    public function __construct()
    {
        $this->dbHost = getenv('DB_HOST');
        $this->dbName = getenv('DB_NAME');
        $this->dbUser = getenv('DB_USER');
        $this->dbPassword = getenv('DB_PASSWORD');
        $this->dbPort = getenv('DB_PORT') ?: 3306;  // default to 3306 if not set
    }

    //methods////////
    // Method to send JSON response
    public function sendResponse($status, $message, $data = [])
    {
        header('Content-Type: application/json');
        $response = [
            'status' => $status,
            'timestamp' => time() * 1000 // Convert to milliseconds
        ];
        if (!empty($message)) {
            $response['data'] = $message;
        } elseif (!empty($data)) {
            $response['data'] = $data;
        }
        echo json_encode($response);
        exit;
    }

    public function TestResponse($requestData)
    {
        try {
            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, (int)$this->dbPort);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }
        $hello = isset($requestData['hello']) ? trim($requestData['hello']) : '';
        $world = isset($requestData['world']) ? trim($requestData['world']) : '';

        if (empty($hello) || empty($world)) {
            $this->sendResponse('error', "missing required fields");
        }

        file_put_contents("debug.json", json_encode($requestData, JSON_PRETTY_PRINT));



        $this->sendResponse("Success", "Hello world back to you!");

    }

    public function Register($requestData)
    {

        try {
            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName, $this->dbPort);

            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
        } catch (mysqli_sql_exception $e) {
            echo "Connection failed: " . $e->getMessage();
            $this->sendResponse("error", $e->getMessage());
            exit;
        }

        $name = isset($requestData["name"]) ? trim($requestData["name"]) : "";
        $surname = isset($requestData["surname"]) ? trim($requestData["surname"]) :"";
        $phone_number = isset($requestData["phone_number"]) ? trim($requestData["phone_number"]) :"";
        $email = isset($requestData["email"]) ? trim($requestData["email"]) :"";
        $password = isset($requestData["password"]) ? trim($requestData["password"]) :"";
        $street_number = isset($requestData["street_number"]) ? trim($requestData["street_number"]) :"";
        $street_name = isset($requestData["street_name"]) ? trim($requestData["street_name"]) :"";
        $suburb = isset($requestData["suburb"]) ? trim($requestData["suburb"]) : "";
        $city = isset($requestData["city"]) ? trim($requestData["city"]) :"";
        $zip_code = isset($requestData["zip_code"]) ? trim($requestData["zip_code"]) : "";
        $user_type = isset($requestData["user_type"]) ? trim($requestData["user_type"]) : "";



        //must have unique email
        if(empty($name) || empty($surname) || empty($phone_number) || empty($email) || empty($password)) {
            $this->sendResponse('error', 'Missing required fields');
        }
        elseif (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/', $email)) {
            $this->sendResponse('emailError', 'Invalid email address');
        }
        elseif (!preg_match("/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/", $password)) {
            $this->sendResponse('passwordError', 'Password does not meet requirements');
        }
        else{
            //check if email is unique
            $emailcheck = $conn->prepare("SELECT * FROM User WHERE email = ?");
            $emailcheck->bindParam("s", $email);
            $emailcheck->execute();

            if($emailcheck->get_result()->fetch_assoc())
            {
                $this->sendResponse("error","Email already exists");
            }   

            //All good so we can insert the user
            //set api key
            $apikey = base64_encode(random_bytes(32));
            $password = password_hash($password, PASSWORD_DEFAULT);
            $sqlInsert = $conn->prepare("INSERT INTO User(name,surname, phone_number,apikey, email, password, street_number, street_name, suburb,city,zip_code, user_type) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $sqlInsert->bind_param("ssssssssssss", $name, $surname, $phone_number, $apikey, $email, $password, $street_number, $street_name, $suburb, $city, $zip_code, $user_type);

            if( $sqlInsert->execute() ) {
                $user_id = mysqli_insert_id( $conn );
                if($user_type === "Customer")
                {
                    $pfp = isset($requestData["profile_picture"]) ? trim($requestData["profile_picture"]) :"";
                    $insertC = $conn->prepare("INSERT INTO Customer(user_id, profile_picture) VALUES (?,?)");
                    $insertC->bind_param("is",$user_id, $pfp);
                    $insertC->execute();
                    $insertC->close();
                }
                else if($user_type === "Admin")
                {
                    $salary = isset($requestData["salary"]) ? trim($requestData["salary"]) : "";
                    $position = isset($requestData["position"]) ? trim($requestData["position"]) : "";
                    $insertA = $conn->prepare("INSERT INTO Admin(user_id, salary, position) VALUES (?,?,?)");
                    $insertA->bind_param("ids", $user_id, $salary, $position);
                    $insertA->execute();
                    $insertA->close();
                }else
                {
                    $this->sendResponse("error","user type unrecognized");
                    return;
                }
                }else{
                    $this->sendResponse("error","User registration failed");
                }

            $sqlInsert->close();
            $conn->close();


        }

    $this->sendResponse("success","Inserted new user");



    }

    public function Login($requestData)
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



        $email = isset($requestData["email"]) ? trim($requestData["email"]) : "";
        $password = isset($requestData["password"]) ? trim($requestData["password"]) :"";

        if(empty($email) || empty($password)){
            $this->sendResponse("error","All fields must be valid");
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


   
    }
    public function ViewAllProducts($requestData)
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

        $stmt = $conn->prepare("SELECT * FROM Product");
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->fetch_assoc())
        {   
            $this->sendResponse("success", $result);
        }
    }
    public function RateProduct($requestData)
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
    }







    ///////////

}


$api = new API();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);

    if (isset($requestData['type'])) {
        if ($requestData['type'] === "Test") {
            $api->TestResponse($requestData);
        } else if ($requestData['type'] === "Login") {
            $api->Login($requestData);
        } else if ($requestData['type'] === "Register") {
            $api->Register($requestData);
        } else if ($requestData['type'] === "ViewAllProducts") {
            $api->ViewAllProducts($requestData);
        } else if ($requestData['type'] === "RateProduct") {
            $api->RateProduct($requestData);
        } else if ($requestData['type'] === "AddProduct") {
            $api->AddProduct($requestData);
        } else if ($requestData['type'] === "UpdateProduct") {
            $api->UpdateProduct($requestData);
        } else if ($requestData['type'] === "DeleteProduct") {
            $api->DeleteProduct($requestData);
        } else if ($requestData['type'] === "ViewRatings") {
            $api->ViewRatings($requestData);
        } else if ($requestData['type'] === "FilterProducts") {
            $api->FilterProducts($requestData);
        }else if ($requestData['type'] === "UpdateCustomer") { //update or delete through here
            $api->FilterProducts($requestData);
        }else if ($requestData['type'] === "UpdateAdmin") { //update or delete through here
            $api->FilterProducts($requestData);
        }else if ($requestData['type'] === "ViewSupplier") { //update or delete through here
            $api->ViewSupplier($requestData);
        } else {
            echo "please specify type";
        }
    } else {
        http_response_code(400);
        $api->sendResponse("error", "Missing or invalid parameters");
    }
} else {
    http_response_code(405);
    $api->sendResponse("error", "Method not allowed");
}




?>