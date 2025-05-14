<?php

/*
function loadEnv($path)
{
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

loadEnv(__DIR__ . '/.env');
*/

header("Access-Control-Allow-Origin: *"); //allow all cors
header("Content-Type: application/json"); 
header("Access-Control-Allow-Methods: POST");



class API
{
    // Access the environment variables
    /*
    private $dbHost = getenv('DB_HOST');
    private $dbName = getenv('DB_NAME');
    private $dbUser = getenv('DB_USER');
    private $dbPassword = getenv('DB_PASSWORD');
    */
    private $conn;

    // Function to load .env variables manually

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
/* //cant do this since i dont have phpmyadmin
        try{
            $conn = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);

            if($conn->connect_error)
            {
                die("Connection failed: " . $conn->connect_error);
            }
        }
        catch(mysqli_sql_exception $e)
        {
            echo "Connection failed: " . $e->getMessage();
            exit;
        }
*/
        $hello = isset($requestData['hello']) ? trim($requestData['hello']) : '';
        $world = isset($requestData['world']) ? trim($requestData['world']) : '';

        if(empty($hello) || empty($world))
        {
            $this->sendResponse('error', "missing required fields");
        }

        file_put_contents("debug.json", json_encode($requestData, JSON_PRETTY_PRINT));

        
        /*
            // Check if email already exists in the database;  
            $stmt = $conn->prepare("SELECT * FROM Users WHERE email=?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

        if ($stmt->get_result()->fetch_assoc()) {
                $this->sendResponse('error', 'Email already exists');
            }
                else{
                                //echo "Inserting user";

                //Hash password and generate API key                        
                $apikey = (base64_encode(random_bytes(32)));
                $password = password_hash($password, PASSWORD_DEFAULT);
                $sqlInsert = $conn->prepare("INSERT INTO Users(name,surname,email,password,apikey) VALUES (?,?,?,?,?)");
                $sqlInsert->bind_param("sssss", $name, $surname, $email, $password, $apikey);
                if ($sqlInsert->execute()) {

                    echo json_encode(['status' => 'success', 'timestamp' => time(), 'data' => ['apikey' => $apiKey]]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Failed to register user']);
                }
                }


        //Example regex matching
        elseif (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/', $email)) {
            $this->sendResponse('emailError', 'Invalid email address');
        } elseif (!preg_match("/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/", $password)) {
            $this->sendResponse('passwordError', 'Password does not meet requirements');
        } else {
        }
        $stmt->close();
        conn->close();
        */

        $this->sendResponse("Success", "Hello world back to you!");
    
    }
    
        
    






    ///////////

}


    $api = new API();

    if($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        $requestData = json_decode(file_get_contents('php://input'), true);

        if(isset($requestData['type']))
        {
            if($requestData['type'] === "Test")
            {
                $api->TestResponse($requestData);
            }
            else if ($requestData['type'] === "Register")
            {

            }
            else {
            echo "please specify type";
        }
        }
        else {
        http_response_code(400);
        $api->sendResponse("error", "Missing or invalid parameters");
    }
    }
    else {
    http_response_code(405);
    $api->sendResponse("error", "Method not allowed");
}




?>