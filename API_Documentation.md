# API Documentation

Our API provides functionality for our price comparison website called "Prick `n Price". It integrates front-end development with our back-end database.

## Authentication

<!-- TODO: Add logic to check for a valid API key before processing a request. Current implementation does not authorise requests! -->
When using the API, the logged-in user's API key should be included in requests as a cookie named `userapikey`. This is to prevent API misuse, and to ensure that the user is authorised to make this type of request. When registering a new user, an API key will be generated and stored in the database. This key should be used for all subsequent requests. When logging in, the API will automatically set the `userapikey` cookie for the user, along with `useremail`, `username`, and `usersurname` cookies. This allows the user to remain logged in for a period of 30 days, unless they log out or clear their cookies.

## Request Format

All requests to the API should be made using the `POST` method. The request body should be in `JSON` format, and the content type should be set to `application/json`. Use a `type` parameter to specify the type of request being made. The following types are available:
- `Login`: Validates the user's credentials  using the request body and their information in the database.
- `Register`: Registers a new user in the database using the request body.
- `ViewAllProducts`: Returns all products in the database.
- `AddProduct`: Adds a new product to the database using the request body.
- `UpdateProduct`: Updates an existing product in the database using the request body.
- `DeleteProduct`: Deletes a product from the database using the request body.
- `ViewRatings`: Returns all of the ratings for a specific product in the database using the request body.
- `FilterProducts`: Filters products in the database based on the request body.
- `UpdateCustomer`: Updates the user's information in the database using the request body.
- `UpdateAdmin`: Updates an admin's information in the database using the request body.
- `ViewSupplier`: Returns the information of a specific supplier in the database using the request body.

See each of the API endpoints below for more details on the request format and response format for each `type` of request.

## Response Format

All responses from the API will be in `JSON` format. The response will include a `status` field indicating the success or failure of the request. A `message` field may be included in the response providing additional information. The response can also include a `data` field containing the requested data. If the request failed, the response will include an `error` field with details about the error.

#### Example Request:
```json
{
    "status": "<status>",
    "timestamp": 1234567890000,
    "message": "Optional message",
    "data": {}
}
```

## API Endpoints
### Test Endpoint
The test endpoint is used to check if the API is working correctly. Send in a request with the `type` parameter set to `Test`, along with a `hello` and a `world` parameter. The API will respond with a message indicating that the test was successful.

| Parameter | Description | Required |
|-----------|-------------|----------|
| `type`   | The request type: Must be set to `Test` | Yes |
| `hello`  | A string parameter to test the API | Yes |
| `world`  | A string parameter to test the API | Yes |

#### Example Request:
```json
{
    "type": "Test",
    "hello": "hello",
    "world": "world"
}
```

#### Example Response (Success):
```json
{
    "status": "success",
    "timestamp": 1234567890000,
    "message": "Hello world back to you!"
}
```

#### Example Response (Error):
```json
{
    "status": "error",
    "timestamp": 1234567890000,
    "message": "missing required fields"
}
```

### Register Endpoint
The register endpoint is used to create a new user account. Send in a request with the `type` parameter set to `Register`, along with the user's information (email, username, password, etc.) in the request body. The API will respond with a message indicating whether the registration was successful.

If the user's email address already exists in the database, the API will respond with an error message indicating that the email is already in use. If the registration is successful, the API will respond with a success message.

The user's password is hashed before being stored in the database for security purposes. The API will also generate an API key for the user, which will be used for authentication in subsequent requests. 

| Parameter | Description | Required |
|-----------|-------------|----------|
| `type`    | The request type: Must be set to `Register` | Yes |
| `name`    | The user's name | Yes |
| `surname` | The user's surname | Yes |
| `phone_number` | The user's phone number | Yes |
| `email`   | The user's email address | Yes |
| `password`| The user's password | Yes |
| `street_number` | The user's street number | No |
| `street_name` | The user's street name | No |
| `suburb` | The user's suburb | No |
| `city` | The user's city | No |
|`zip_code` | The user's zip code | No |
| `user_type` | The user's type (`Customer` or `Admin`) | Yes | <!-- TODO: Add logic to check if the user type is valid BEFORE inserting into the USER table! -->
| `profile_picture` | For `Customer`s: The user's profile picture URL | No |
| `salary` | For `Admin`s: The user's salary | No |
| `position` | For `Admin`s: The user's position in the company | No |

#### Example Request:
```json
{
    "type": "Register",
    "name": "John",
    "surname": "Doe",
    "phone_number": "0721234567",
    "email": "user@example.com",
    "password": "MyPassword@123!",
    "user_type": "Customer"
}
```

#### Example Response (Success):
```json
{
    "status": "success",
    "timestamp": 1234567890000,
    "message": "Inserted new user"
}
```

#### Example Response (Error):
```json
{
    "status": "error",
    "timestamp": 1234567890000,
    "message": "Email already exists"
}
```

### Login Endpoint
<!-- TODO: Verify the API response. Currently it seems like the response sends back an array of the user's data as message and not as data. -->
The login endpoint is used to authenticate a user. Send in a request with the `type` parameter set to `Login`, along with the user's email and password in the request body. The API will respond with a message indicating whether the login was successful or not. If the login is successful, the API will set the `userapikey` cookie for the user, along with `useremail`, `username`, and `usersurname` cookies and return this information in the response body. If the login fails, the API will respond with an error message indicating that the email or password is incorrect.

| Parameter | Description | Required |
|-----------|-------------|----------|
| `type`    | The request type: Must be set to `Login` | Yes |
| `email`   | The user's email address | Yes |
| `password`| The user's password | Yes |

#### Example Request:
```json
{
    "type": "Login",
    "email": "user@example.com",
    "password": "password123"
}
```

#### Example Response (Success):
```json
{
    "status": "success",
    "timestamp": 1234567890000,
    "data": {
        "userapikey": "<api_key>",
        "useremail": "user@example.com",
        "username": "John",
        "usersurname": "Doe"
    }
}
```

#### Example Response (Error):
```json
{
    "status": "error",
    "timestamp": 1234567890000,
    "message": "Unknown email or password"
}
```

### ViewAllProducts Endpoint
The view all products endpoint is used to retrieve all the information of all the products in the database. Send a request with the `type` parameter set to `ViewAllProducts`. The API will respond with a message indicating whether the request was successful or not. If the request is successful, the API will return all the products in the database.

| Parameter | Description | Required |
|-----------|-------------|----------|
| `type`    | The request type: Must be set to `ViewAllProducts` | Yes |
| `userapikey` | The user's API key | Yes | <!-- TODO: The API key is not validated before returning products at the moment -->

#### Example Request:
```json
{
    "type": "ViewAllProducts",
    "userapikey": "<api_key>"
}
```

#### Example Response (Success):
```json
{
    "status": "success",
    "timestamp": 1234567890000,
    "data": [
        {
            "id": 1,
            "name": "Memory Card Reader",
            "category": "Storage",
            "description": "USB card reader for SD and microSD cards.",
            "price": 285.55,
            "retailer_id": 1,
            "image_url": "https://images.pexels.com/photos/7610457/pexels-photo-7610457.jpeg?auto=compress&cs=tinysrgb&h=350"
        },
        {
            "id": 2,
            "name": "Product 2",
            ...
        }
    ]
}
```
