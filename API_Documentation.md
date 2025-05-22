# API Documentation

Our API provides functionality for our price comparison website called "Prick `n Price". It integrates front-end development with our back-end database.

## Authentication

When using the API, the logged-in user's API key should be included in requests as a cookie named `userapikey` or as a field in the request body as `apikey`. This is to prevent API misuse and to ensure that the user is authorised to make this type of request. When registering a new user, an API key will be generated and stored in the database. This key should be used for all subsequent requests. When logging in, the API will automatically set the `userapikey` cookie for the user, along with `useremail`, `username`, and `usersurname` cookies. This allows the user to remain logged in for a period of 30 days, unless they log out or clear their cookies.

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

All responses from the API will be in `JSON` format. The response will include a `status` field indicating the success or failure of the request, a `timestamp` field, and may include a `message` and/or `data` field as follows:

- **Success Cases:**
  - If returning records/objects, a `data` field will be included. `message` may also be specified but will not contain usable data.
  - If the operation is successful but does not return data, include a `message` field will be included without any `data`.

- **Error Cases:**
  - Always includes a `message` field with description of the error.
  - Should not include a `data` field.

#### Example Response (Success with Data):
```json
{
    "status": "success",
    "timestamp": 1234567890000,
    "data": {
        "some": "object",
        "or": "array"
    }
}
```

#### Example Response (Success with Message):
```json
{
    "status": "success",
    "timestamp": 1234567890000,
    "message": "User created successfully"
}
```

#### Example Response (Error):
```json
{
    "status": "error",
    "timestamp": 1234567890000,
    "message": "Missing required fields"
}
```

#### Example Response (Error with Data):
```json
{
    "status": "error",
    "timestamp": 1234567890000,
    "message": "Validation failed",
    "data": {
        "email": "Invalid email address"
    }
}
```
--- 

## API Endpoints

### Test Endpoint

The test endpoint is used to check if the API is working correctly. Send a request with the `type` parameter set to `Test`, along with some parameters. The API will respond with a `data` field containing a message and all of the parameters received.

| Parameter | Description | Required |
|-----------|-------------|----------|
| `type`   | The request type: Must be set to `Test` | Yes |

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
    "timestamp": 1747921217000,
    "code": 200,
    "message": "Test Successful!",
    "data": {
        "type": "Test",
        "hello": "hello",
        "world": "world"
    }
}
```

### Register Endpoint

The register endpoint is used to create a new user account. Send a request with the `type` parameter set to `Register`, along with the user's information in the request body. The API will respond with a `message` and `data` if the registration is successful, or with a `message` and `data` describing the errors if registration fails. Users are registered as customers by default. The API will also generate an API key for the user, store it in the database, and return it upon successful registration. This `API key` should be used for all subsequent requests from this user.

#### Validation Rules

- **name**: Only letters, max 50 characters. **Required**
- **surname**: Only letters, max 50 characters. **Required**
- **phone_number**: Exactly 10 digits without spaces (e.g., `0726206863`). *Optional, but if provided, must be valid*
- **email**: Valid email, max 100 characters. **Required**
- **password**: At least 8 characters, must include upper and lower case letters, a number, and a special character. **Required**
- **street_number**: Max 10 characters. *Optional, but if provided, must be valid*
- **street_name**: Only letters and spaces, max 100 characters. *Optional, but if provided, must be valid*
- **suburb**: Only letters and spaces, max 100 characters. *Optional, but if provided, must be valid*
- **city**: Only letters and spaces, max 100 characters. *Optional, but if provided, must be valid*
- **zip_code**: Max 5 characters. *Optional, but if provided, must be valid*

#### Request Parameters

| Parameter        | Description                        | Required |
|------------------|------------------------------------|----------|
| `type`           | The request type: Must be `Register` | Yes      |
| `name`           | The user's name (only letters, max 50 chars) | Yes      |
| `surname`        | The user's surname (only letters, max 50 chars) | Yes      |
| `phone_number`   | The user's phone number (exactly 10 digits) | No       |
| `email`          | The user's email address (valid, max 100 chars) | Yes      |
| `password`       | The user's password (min 8 chars, upper/lower/number/special char) | Yes      |
| `street_number`  | The user's street number (max 10 chars) | No       |
| `street_name`    | The user's street name (only letters and spaces, max 100 chars) | No       |
| `suburb`         | The user's suburb (only letters and spaces, max 100 chars) | No       |
| `city`           | The user's city (only letters and spaces, max 100 chars) | No       |
| `zip_code`       | The user's zip code (max 5 chars) | No       |

> **Note:**  
> If any optional parameters are provided, they will be validated according to the rules above before registering the user with them.  
> If an optional field is omitted, it will be stored as `NULL` in the database.

#### Example Request

```json
{
    "type": "Register",
    "name": "Pieter",
    "surname": "Wenning",
    "email": "pieterwenning@gmail.com",
    "password": "BadPass@123!"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747920608000,
    "code": 201,
    "message": "User registered successfully",
    "data": {
        "user_id": 9999,
        "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef"
    }
}
```

#### Example Response (Error: Email Exists)
```json
{
    "status": "error",
    "timestamp": 1747920923000,
    "code": 409,
    "message": "Email already exists: Please use a different email or log into your account"
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747921194000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "name": "Name must be only letters and max 50 characters",
        "surname": "Surname must be only letters and max 50 characters"
    }
}
```

### Login Endpoint

The login endpoint is used to authenticate a user. Send a request with the `type` parameter set to `Login`, along with the user's `email` and `password` in the request body. The API will respond with a message indicating whether the login was successful or not. If the login is successful, the API will return the user's API key, name, and user type in `data` in the response body. If the login fails, the API will return an error message indicating the reason for the failure.

#### Request Parameters

| Parameter   | Description                | Required |
|-------------|----------------------------|----------|
| `type`      | The request type: Must be set to `Login` | Yes      |
| `email`     | The user's email address   | Yes      |
| `password`  | The user's password        | Yes      |

> **Note:**  
> - The `password` is not validated against its regex pattern during login, only during registration: The API will not respond with a validation error if the entered password does not meet the minimum password requirements.
> - On successful login, use the returned `apikey` for future requests.

#### Example Request

```json
{
    "type": "Login",
    "email": "pieterwenning@gmail.com",
    "password": "BadPass@123!"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747924066000,
    "code": 200,
    "message": "User logged in successfully",
    "data": {
        "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
        "name": "Pieter",
        "user_type": "Customer"
    }
}
```

#### Example Response (Error: Invalid Credentials)

```json
{
    "status": "error",
    "timestamp": 1747924106000,
    "code": 401,
    "message": "Invalid email or password"
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747923898000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "email": "Email must be valid and max 100 characters",
        "password": "Error: The password field is required."
    }
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
