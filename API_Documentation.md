# API Documentation

Our API provides functionality for our price comparison website called "Prick `n Price". It integrates front-end development with our back-end database.

## Table of Contents
- [API Documentation](#api-documentation)
  - [Table of Contents](#table-of-contents)
  - [Authentication](#authentication)
  - [Request Format](#request-format)
  - [Response Format](#response-format)
  - [API Endpoints (ADMIN)](#api-endpoints-admin)
    - [Test Endpoint](#test-endpoint)
    - [Register Endpoint](#register-endpoint)
    - [Login Endpoint](#login-endpoint)
    - [QuickAddUser Endpoint](#quickadduser-endpoint)
    - [QuickEditProductPrice Endpoint](#quickeditproductprice-endpoint)
    - [AdminRecentReviews Endpoint](#adminrecentreviews-endpoint)
    - [AddNewProduct Endpoint](#addnewproduct-endpoint)
    - [getAllProducts Endpoint (for Admin)](#getallproducts-endpoint-for-admin)
    - [deleteProduct Endpoint](#deleteproduct-endpoint)
    - [GetAllRetailers Endpoint](#getallretailers-endpoint)
    - [AddRetailer Endpoint](#addretailer-endpoint)
    - [EditRetailer Endpoint](#editretailer-endpoint)
    - [getAllUsers Endpoint](#getallusers-endpoint)
    - [AddNewStaff Endpoint](#addnewstaff-endpoint)
    - [editUser Endpoint](#edituser-endpoint)
    - [deleteUser Endpoint](#deleteuser-endpoint)
    - [deleteRating Endpoint](#deleterating-endpoint)
    - [deleteRetailer Endpoint](#deleteretailer-endpoint)
    - [editProduct Endpoint](#editproduct-endpoint)
  - [API Endpoints (CUSTOMER)](#api-endpoints-customer)
    - [getAllCategories Endpoint](#getallcategories-endpoint)
    - [getAllProducts Endpoint (for Customer)](#getallproducts-endpoint-for-customer)


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

## API Endpoints (ADMIN)

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

- **name**: Only letters, max 50 characters. 
- **surname**: Only letters, max 50 characters. 
- **phone_number**: Exactly 10 digits without spaces (e.g., `0726206863`). 
- **email**: Valid email, max 100 characters. 
- **password**: At least 8 characters, must include upper and lower case letters, a number, and a special character.
- **street_number**: Max 10 characters. 
- **street_name**: Only letters and spaces, max 100 characters. 
- **suburb**: Only letters and spaces, max 100 characters. 
- **city**: Only letters and spaces, max 100 characters. 
- **zip_code**: Max 5 characters. 

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
> The `password` is not validated against its regex pattern during login, only during registration: The API will not respond with a validation error if the entered password does not meet the minimum password requirements.
> On successful login, use the returned `apikey` for future requests.

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

### QuickAddUser Endpoint

The `QuickAddUser` endpoint allows an admin to quickly add a new user (either a Customer or Admin) with only the required fields. The endpoint validates the provided fields, generates a secure password hash and API key, and inserts the user into the appropriate tables. All other user fields are set to `NULL` by default.

- If `user_type` is `Customer`, the user is added to the `Customer` table.
- If `user_type` is `Admin`, the user is added to the `Admin_staff` table (with `salary` and `position` as `NULL`).
- All other fields in the `User` table are set to `NULL`.
- Returns the new user's `user_id` and generated `apikey` on success.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `QuickAddUser`  | Yes      |
| `apikey`    | The API key of the admin performing the request  | Yes      |
| `name`      | The user's name (only letters, max 50 chars)     | Yes      |
| `surname`   | The user's surname (only letters, max 50 chars)  | Yes      |
| `email`     | The user's email address (valid, max 100 chars)  | Yes      |
| `user_type` | The type of user: `Admin` or `Customer`          | Yes      |
| `password`  | The user's password (min 8 chars, upper/lower/number/special char) | Yes |

#### Validation Rules

- **name**: Only letters, max 50 characters.
- **surname**: Only letters, max 50 characters.
- **email**: Must be a valid email address and max 100 characters.
- **user_type**: Must be either `Admin` or `Customer`.
- **password**: At least 8 characters, must include upper and lower case letters, a number, and a special character.

#### Example Request

```json
{
    "type": "QuickAddUser",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "name": "Alice",
    "surname": "Smith",
    "email": "alice.smith@example.com",
    "user_type": "Admin",
    "password": "AdminPass@123"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747926689000,
    "code": 201,
    "message": "User added successfully",
    "data": {
        "user_id": 10000,
        "apikey": "3136bd343d6b847b846d88671aa9f4e133a24c84ed2baf7cc4f5652fdbc044ad"
    }
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747926761000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "name": "Name must be only letters and max 50 characters",
        "password": "Password must be at least 8 characters with upper/lower case, number, and special character"
    }
}
```

#### Example Response (Error: Email Exists)

```json
{
    "status": "error",
    "timestamp": 1747927399000,
    "code": 409,
    "message": "Email already exists: Please use a different email"
}
```

#### Example Response (Error: Not Admin)
```json
{
    "status": "error",
    "timestamp": 1747926519000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

### QuickEditProductPrice Endpoint

The `QuickEditProductPrice` endpoint allows an admin to update or add the price of a product for a specific retailer. If the product-retailer combination already exists in the `SUPPLIED_BY` table, the price is updated. If it does not exist, a new entry is created.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter     | Description                                      | Required |
|---------------|--------------------------------------------------|----------|
| `type`        | The request type: Must be set to `QuickEditProductPrice` | Yes      |
| `apikey`      | The API key of the admin performing the request  | Yes      |
| `product_id`  | The ID of the product (integer)                  | Yes      |
| `retailer_id` | The ID of the retailer (integer)                 | Yes      |
| `price`       | The price to set (float)                         | Yes      |

#### Validation Rules

- **apikey**: Must be a valid API key for an admin user.
- **product_id**: Must be an integer and refer to an existing product.
- **retailer_id**: Must be an integer and refer to an existing retailer.
- **price**: Must be a valid number (float).

#### Example Request

```json
{
    "type": "QuickEditProductPrice",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "product_id": 123,
    "retailer_id": 45,
    "price": 99.99
}
```

#### Example Response (Success: Updated)

```json
{
    "status": "success",
    "timestamp": 1747927792000,
    "code": 200,
    "message": "Product price updated successfully",
    "data": {
        "product_id": 1,
        "retailer_id": 1,
        "price": 99.99
    }
}
```

#### Example Response (Success: Added)

```json
{
    "status": "success",
    "timestamp": 1747927828000,
    "code": 201,
    "message": "Product price added successfully",
    "data": {
        "product_id": 1,
        "retailer_id": 9,
        "price": 99.99
    }
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747927849000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "product_id": "Product ID is required and must be an integer.",
        "price": "Price is required and must be a floating point number."
    }
}
```

#### Example Response (Error: Product or Retailer Not Found)

```json
{
    "status": "error",
    "timestamp": 1747927300000,
    "code": 404,
    "message": "Product does not exist"
}
```
or
```json
{
    "status": "error",
    "timestamp": 1747927870000,
    "code": 404,
    "message": "Retailer does not exist"
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747927500000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```


### AdminRecentReviews Endpoint

The `AdminRecentReviews` endpoint allows an admin to fetch the most recent reviews from the `Rating` table. By default, it returns the 4 most recent reviews, but you can specify a different number using the `number` parameter. Reviews are sorted by the `updated_at` column (the most recent review will be first in the returned array), which is automatically updated whenever a review is created or modified.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `AdminRecentReviews` | Yes      |
| `apikey`    | The API key of the admin performing the request  | Yes      |
| `number`    | The number of recent reviews to fetch (default: 4) | No       |

#### Example Request

```json
{
    "type": "AdminRecentReviews",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "number": 2
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747929000000,
    "code": 200,
    "message": "Recent reviews fetched successfully",
    "data": [
        {
            "id": 101,
            "score": 5,
            "description": "Great product!",
            "user_id": 12,
            "product_id": 7,
            "updated_at": "2024-06-01 14:23:45"
        },
        {
            "id": 100,
            "score": 4,
            "description": "Good value.",
            "user_id": 15,
            "product_id": 7,
            "updated_at": "2024-06-01 13:10:22"
        }
    ]
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747929100000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747929200000,
    "code": 401,
    "message": "API key is required to authenticate user"
}
```

### AddNewProduct Endpoint

The `AddNewProduct` endpoint allows an admin to add a new product to the database. The product must have a name (max 100 characters). The description, image URL, and category are optional and will be set to `NULL` if not provided. Optionally, a `retailer_id` and `price` can be specified; if both are provided and valid, the product will be linked to the retailer in the `Supplied_By` table with the given price.

If the product already exists in the `Product` table (its name is already in the `Product` table), it will not be added again. If `retailer_id` and `price` are provided for a duplicate product, the API will only update or insert the price in the `Supplied_By` table for that product and retailer.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter      | Description                                      | Required |
|----------------|--------------------------------------------------|----------|
| `type`         | The request type: Must be set to `AddNewProduct` | Yes      |
| `apikey`       | The API key of the admin performing the request  | Yes      |
| `name`         | The product name (max 100 characters)            | Yes      |
| `description`  | The product description                          | No       |
| `image_url`    | The product image URL (max 255 characters)       | No       |
| `category`     | The product category (max 100 characters)        | No       |
| `retailer_id`  | The retailer's ID (integer, must exist)          | No*      |
| `price`        | The price for the retailer (float)               | No*      |

> **Note:**  
> `retailer_id` and `price` must both be provided together if you want to link the product to a retailer. If only one is provided, the request will fail validation.
> If the `retailer_id` links to a retailer that does not exist, the request will fail validation.
> If the product already exists in the `Product` table (its name is already in the `Product` table), it will not be added again. If `retailer_id` and `price` are provided for a duplicate product, the API will only update or insert the price in the `Supplied_By` table for that product and retailer.
> If any field is invalid, the product will not be added and no changes will be made to the database (the price for this retailer will not be updated or added either)

#### Validation Rules

- **name**: Required, max 100 characters.
- **image_url**: Optional, max 255 characters.
- **category**: Optional, max 100 characters.
- **retailer_id**: Optional, must be an integer and refer to an existing retailer if provided.
- **price**: Optional, must be a valid float if provided.
- If either `retailer_id` or `price` is provided, both must be present and valid.

#### Example Request

```json
{
    "type": "AddNewProduct",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "name": "Super Widget",
    "description": "A great widget for all your needs.",
    "image_url": "https://example.com/widget.jpg",
    "category": "Widgets",
    "retailer_id": 5,
    "price": 49.99
}
```

#### Example Response (Success: Product and Price Added)

```json
{
    "status": "success",
    "timestamp": 1747930840000,
    "code": 201,
    "message": "Product and price added successfully",
    "data": {
        "product_id": 56,
        "name": "Super Widget",
        "description": "A great widget for all your needs.",
        "image_url": "https://example.com/widget.jpg",
        "category": "Widgets",
        "retailer_id": 5,
        "price": 49.99
    }
}
```

#### Example Response (Success: Product Added Only)

```json
{
    "status": "success",
    "timestamp": 1747930894000,
    "code": 201,
    "message": "Product added successfully",
    "data": {
        "product_id": 57,
        "name": "Super Widget",
        "description": "A great widget for all your needs.",
        "image_url": "https://example.com/widget.jpg",
        "category": "Widgets"
    }
}
```

#### Example Response (Success: Product Already Exists, Price Updated)

```json
{
    "status": "success",
    "timestamp": 1747930924000,
    "code": 200,
    "message": "Product price updated successfully",
    "data": {
        "product_id": 57,
        "retailer_id": 5,
        "price": 49.99
    }
}
```

#### Example Response (Success: Product Already Exists, No Price Update)

```json
{
    "status": "success",
    "timestamp": 1747930938000,
    "code": 200,
    "message": "Product already exists",
    "data": {
        "product_id": 57,
        "name": "Super Widget",
        "description": "A great widget for all your needs.",
        "image_url": "https://example.com/widget.jpg",
        "category": "Widgets"
    }
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747930963000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "name": "Product name is required.",
        "retailer_id": "Retailer ID and price must be provided together.",
        "price": "Retailer ID and price must be provided together."
    }
}
```

#### Example Response (Error: Retailer Not Found)

```json
{
    "status": "error",
    "timestamp": 1747930987000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "retailer_id": "Retailer ID does not exist."
    }
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747930300000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

### getAllProducts Endpoint (for Admin)

The `getAllProducts` endpoint allows an admin to retrieve all products in the database. This endpoint returns all columns for every product in the `Product` table. No sorting or filtering is applied.

**Depending on the user type (as identified by their API key), the user will automatically be directed to their respective getAllProducts handler.**

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `getAllProducts`| Yes      |
| `apikey`    | The API key of the admin performing the request  | Yes      |

> **Note:**  
> This endpoint returns all products with all their fields as stored in the `Product` table.
> No filtering or sorting is performed.

#### Example Request

```json
{
    "type": "getAllProducts",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA="
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747932000000,
    "code": 200,
    "message": "All products fetched successfully",
    "data": [
        {
            "id": 1,
            "name": "Super Widget",
            "description": "A great widget for all your needs.",
            "image_url": "https://example.com/widget.jpg",
            "category": "Widgets"
        },
        {
            "id": 2,
            "name": "Mega Gadget",
            "description": "The best gadget on the market.",
            "image_url": "https://example.com/gadget.jpg",
            "category": "Gadgets"
        }
    ]
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747932100000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

#### Example Response (Error: Invalid API Key)

```json
{
    "status": "error",
    "timestamp": 1747932200000,
    "code": 401,
    "message": "Invalid API key or User not found"
}
```

### deleteProduct Endpoint

The `deleteProduct` endpoint allows an admin to delete a product from the database by its `product_id`. The product will only be deleted if it exists. If the product has associated data (e.g., reviews, prices, etc.), those will also be deleted. If the product does not exist, an error will be returned.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter     | Description                                      | Required |
|---------------|--------------------------------------------------|----------|
| `type`        | The request type: Must be set to `deleteProduct` | Yes      |
| `apikey`      | The API key of the admin performing the request  | Yes      |
| `product_id`  | The ID of the product to delete (integer)        | Yes      |
> **Note:**  
> All ratings and price entries for this product are deleted from the `Rating` and `Supplied_By` tables before the product itself is deleted.

#### Example Request

```json
{
    "type": "deleteProduct",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "product_id": 57
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747933000000,
    "code": 200,
    "message": "Product deleted successfully",
    "data": {
        "product_id": 57
    }
}
```

#### Example Response (Error: Product Not Found)

```json
{
    "status": "error",
    "timestamp": 1747933100000,
    "code": 404,
    "message": "Product does not exist"
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747933200000,
    "code": 422,
    "message": "Product ID is required and must be an integer."
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747933300000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

### GetAllRetailers Endpoint

The `GetAllRetailers` endpoint allows an admin to retrieve all retailers in the database. This endpoint returns all columns for every retailer in the `Retailer` table, sorted in ascending order by name. No filtering is applied.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `GetAllRetailers`| Yes      |
| `apikey`    | The API key of the admin performing the request  | Yes      |

#### Example Request
```json
{
    "type": "GetAllRetailers",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA="
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747934658000,
    "code": 200,
    "message": "All retailers fetched successfully",
    "data": [
        {
            "id": "9",
            "name": "Aivee",
            "email": "nleroy8@hubpages.com",
            "suburb": "Maple Ridge",
            "city": "Kedungbulu",
            "street_name": "Kings",
            "street_number": "932",
            "zip_code": "5931"
        },
        {
            "id": "4",
            "name": "Camido",
            "email": "mconquer3@squarespace.com",
            "suburb": "Willow Creek",
            "city": "Fenghuangshan",
            "street_name": "Eagle Crest",
            "street_number": "73",
            "zip_code": "2304"
        }
    ]
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747934100000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

#### Example Response (Error: Invalid API Key)

```json
{
    "status": "error",
    "timestamp": 1747934200000,
    "code": 401,
    "message": "Invalid API key or User not found"
}
```

---

### AddRetailer Endpoint

The `AddRetailer` endpoint allows an admin to add a new retailer to the database. The retailer must have a unique name and a valid email address (max 100 characters each). Other address fields are optional and will be set to `NULL` if not provided.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter        | Description                                      | Required |
|------------------|--------------------------------------------------|----------|
| `type`           | The request type: Must be set to `AddRetailer`   | Yes      |
| `apikey`         | The API key of the admin performing the request  | Yes      |
| `name`           | The retailer's name (max 100 characters, unique) | Yes      |
| `email`          | The retailer's email (valid, max 100 characters) | Yes      |
| `street_number`  | The retailer's street number (max 100 chars)     | No       |
| `street_name`    | The retailer's street name (max 100 chars)       | No       |
| `suburb`         | The retailer's suburb (max 100 chars)            | No       |
| `city`           | The retailer's city (max 100 chars)              | No       |
| `zip_code`       | The retailer's zip code (max 10 chars)           | No       |

> **Note:**  
> If any optional parameters are omitted, they will be stored as `NULL` in the database. If they are included, they must be valid according to the rules above.

#### Example Request

```json
{
    "type": "AddRetailer",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "name": "Retailer C",
    "email": "retc@example.com",
    "street_number": "15",
    "street_name": "Market St",
    "suburb": "CBD",
    "city": "Cape Town",
    "zip_code": "8000"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747934805000,
    "code": 201,
    "message": "Retailer added successfully",
    "data": {
        "retailer_id": 11,
        "name": "Retailer C",
        "email": "retc@example.com",
        "street_number": "15",
        "street_name": "Market St",
        "suburb": "CBD",
        "city": "Cape Town",
        "zip_code": "8000"
    }
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747934400000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "name": "Retailer name is required and must be at most 100 characters.",
        "email": "Valid email is required and must be at most 100 characters."
    }
}
```

#### Example Response (Error: Retailer Exists)

```json
{
    "status": "error",
    "timestamp": 1747934500000,
    "code": 409,
    "message": "Retailer already exists with this name."
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747934600000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

---

### EditRetailer Endpoint

The `EditRetailer` endpoint allows an admin to update the information of an existing retailer. The retailer is identified by `retailer_id`. Only the fields provided in the request will be updated; fields not included in the request will remain unchanged. If there is already a retailer with the same name, the request will fail validation. The `retailer_id` must refer to an existing retailer.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter        | Description                                      | Required |
|------------------|--------------------------------------------------|----------|
| `type`           | The request type: Must be set to `EditRetailer`  | Yes      |
| `apikey`         | The API key of the admin performing the request  | Yes      |
| `retailer_id`    | The ID of the retailer to update (integer)       | Yes      |
| `name`           | The retailer's name (max 100 characters)         | No       |
| `email`          | The retailer's email (valid, max 100 characters) | No       |
| `street_number`  | The retailer's street number (max 100 chars)     | No       |
| `street_name`    | The retailer's street name (max 100 chars)       | No       |
| `suburb`         | The retailer's suburb (max 100 chars)            | No       |
| `city`           | The retailer's city (max 100 chars)              | No       |
| `zip_code`       | The retailer's zip code (max 10 chars)           | No       |

> **Note:**  
> Only the fields provided in the request will be updated.  
> If no updatable fields are provided, the request will fail validation.

#### Example Request

```json
{
    "type": "EditRetailer",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "retailer_id": 2,
    "email": "newemail@retailer.com",
    "city": "Johannesburg"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747934904000,
    "code": 200,
    "message": "Retailer updated successfully",
    "data": {
        "retailer_id": 2
    }
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747934800000,
    "code": 422,
    "message": "No fields provided to update."
}
```

#### Example Response (Error: Retailer Not Found)

```json
{
    "status": "error",
    "timestamp": 1747934900000,
    "code": 404,
    "message": "Retailer does not exist."
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747935000000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

### getAllUsers Endpoint

The `getAllUsers` endpoint allows an admin to retrieve all users in the database. For each user, all columns from the `User` table are included. If the user is a Customer, all fields from the `Customer` table (except `user_id`) are also included. If the user is an Admin, all fields from the `Admin_staff` table (except `user_id`) are also included. The results are sorted by user ID in ascending order. 

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `getAllUsers`   | Yes      |
| `apikey`    | The API key of the admin performing the request  | Yes      |
> **Note:**
> This endpoint does not return the user's password, API key or salt.

#### Example Request

```json
{
    "type": "getAllUsers",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA="
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747936000000,
    "code": 200,
    "message": "All users fetched successfully",
    "data": [
        {
            "id": "9999",
            "name": "Pieter",
            "surname": "Wenning",
            "phone_number": null,
            "email": "pieterwenning@gmail.com",
            "street_number": null,
            "street_name": null,
            "suburb": null,
            "city": null,
            "zip_code": null,
            "user_type": "Customer"
        },
        {
            "id": "10000",
            "name": "Alice",
            "surname": "Smith",
            "phone_number": null,
            "email": "alice.smith@example.com",
            "street_number": null,
            "street_name": null,
            "suburb": null,
            "city": null,
            "zip_code": null,
            "user_type": "Admin",
            "salary": null,
            "position": null
        }
    ]
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747936100000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

### AddNewStaff Endpoint

The `AddNewStaff` endpoint allows an admin to add a new admin staff member to the system. The staff member is added to both the `User` and `Admin_staff` tables. The API will generate a secure password hash, salt, and API key for the new staff member. Returns the new user's `user_id` and generated `apikey` on success.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter      | Description                                             | Required |
|----------------|---------------------------------------------------------|----------|
| `type`         | The request type: Must be set to `AddNewStaff`          | Yes      |
| `apikey`       | The API key of the admin performing the request         | Yes      |
| `name`         | The staff member's name (only letters, max 50 chars)    | Yes      |
| `surname`      | The staff member's surname (only letters, max 50 chars) | Yes      |
| `email`        | The staff member's email (valid, max 100 chars)         | Yes      |
| `phone_number` | The staff member's phone number (exactly 10 digits)     | Yes      |
| `password`     | The staff member's password (min 8 chars, upper/lower/number/special char) | Yes |
| `position`     | The staff member's position (max 100 chars)             | Yes      |
| `salary`       | The staff member's salary (number, max 2 decimals)      | Yes      |

#### Validation Rules

- **name**: Only letters, max 50 characters.
- **surname**: Only letters, max 50 characters.
- **email**: Must be a valid email address and max 100 characters.
- **phone_number**: Exactly 10 digits.
- **password**: At least 8 characters, must include upper and lower case letters, a number, and a special character.
- **position**: Max 100 characters.
- **salary**: Must be a valid number (float, max 2 decimals).

#### Example Request

```json
{
    "type": "AddNewStaff",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "name": "Bob",
    "surname": "Jones",
    "email": "bob.jones@example.com",
    "phone_number": "0721234567",
    "password": "AdminPass@123",
    "position": "Manager",
    "salary": "50000"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747936429000,
    "code": 201,
    "message": "Staff member added successfully",
    "data": {
        "user_id": 10002,
        "apikey": "86a64d2f1a72702e2fefec452987f59564f5b4d0a60f6d5190a7ade832ebc5b1"
    }
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747937100000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "name": "Name must be only letters and max 50 characters",
        "salary": "Salary must be a valid number"
    }
}
```

#### Example Response (Error: Email Exists)

```json
{
    "status": "error",
    "timestamp": 1747937200000,
    "code": 409,
    "message": "Email already exists: Please use a different email"
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747937300000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

---

### editUser Endpoint

The `editUser` endpoint allows an admin to update any user's information. The user is identified by their `id`. Only the fields provided in the request will be updated; fields not included in the request will remain unchanged. If the `user_type` is changed, the user will be removed from their old type table and added to the new one. If the user is an admin and `position` or `salary` is provided, those fields will be updated in the `Admin_staff` table.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter      | Description                                             | Required |
|----------------|---------------------------------------------------------|----------|
| `type`         | The request type: Must be set to `editUser`             | Yes      |
| `apikey`       | The API key of the admin performing the request         | Yes      |
| `id`           | The ID of the user to update (integer)                  | Yes      |
| `name`         | The user's name (only letters, max 50 chars)            | No       |
| `surname`      | The user's surname (only letters, max 50 chars)         | No       |
| `email`        | The user's email (valid, max 100 chars)                 | No       |
| `phone_number` | The user's phone number (exactly 10 digits)             | No       |
| `password`     | The user's password (min 8 chars, upper/lower/number/special char) | No |
| `user_type`    | The user's type: `Admin` or `Customer`                  | No       |
| `position`     | The admin's position (max 100 chars, if user is admin)  | No       |
| `salary`       | The admin's salary (number, max 2 decimals, if admin)   | No       |

> **Note:**  
> - Only the fields provided in the request will be updated.  
> - If `user_type` is changed, the user will be removed from their old type table and added to the new one.
> - If the user is an admin and `position` or `salary` is provided, those fields will be updated in the `Admin_staff` table.
> - If no updatable fields are provided, the request will fail validation.

#### Example Request

```json
{
    "type": "editUser",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "id": 10002,
    "email": "bob.jones@newmail.com",
    "position": "Senior Manager",
    "salary": "60000"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747938000000,
    "code": 200,
    "message": "User updated successfully",
    "data": {
        "user_id": 10002
    }
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747938100000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "email": "Email must be valid and max 100 characters"
    }
}
```

#### Example Response (Error: User Not Found)

```json
{
    "status": "error",
    "timestamp": 1747938200000,
    "code": 404,
    "message": "User does not exist."
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747938300000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

### deleteUser Endpoint

The `deleteUser` endpoint allows an admin to delete a user from the database by their `user_id`. The user will be deleted from the `User` table and from the `Customer` or `Admin_staff` table, depending on their user type. If the user does not exist, an error will be returned.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `deleteUser`    | Yes      |
| `apikey`    | The API key of the admin performing the request  | Yes      |
| `user_id`   | The ID of the user to delete (integer)           | Yes      |
> **Note:**  
> All ratings by this user are deleted from the `Rating` table and the user is removed from the `Customer` or `Admin_staff` table before the user is deleted from the `User` table

#### Example Request

```json
{
    "type": "deleteUser",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "user_id": 10002
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747939000000,
    "code": 200,
    "message": "User deleted successfully",
    "data": {
        "user_id": 10002
    }
}
```

#### Example Response (Error: User Not Found)

```json
{
    "status": "error",
    "timestamp": 1747939100000,
    "code": 404,
    "message": "User does not exist"
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747939200000,
    "code": 422,
    "message": "User ID is required and must be an integer."
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747939300000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

### deleteRating Endpoint

The `deleteRating` endpoint allows an admin to delete a rating (review) for a specific user and product. The request must include both the `user_id` and `product_id`. The endpoint checks that both the user and product exist, and that a rating exists for this user-product combination before deleting it.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter    | Description                                      | Required |
|--------------|--------------------------------------------------|----------|
| `type`       | The request type: Must be set to `deleteRating`  | Yes      |
| `apikey`     | The API key of the admin performing the request  | Yes      |
| `user_id`    | The ID of the user who wrote the rating (integer)| Yes      |
| `product_id` | The ID of the product being rated (integer)      | Yes      |

#### Example Request

```json
{
    "type": "deleteRating",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "user_id": 10002,
    "product_id": 57
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747940000000,
    "code": 200,
    "message": "Rating deleted successfully",
    "data": {
        "user_id": 10002,
        "product_id": 57
    }
}
```

#### Example Response (Error: User Not Found)

```json
{
    "status": "error",
    "timestamp": 1747940100000,
    "code": 404,
    "message": "User does not exist."
}
```

#### Example Response (Error: Product Not Found)

```json
{
    "status": "error",
    "timestamp": 1747940200000,
    "code": 404,
    "message": "Product does not exist."
}
```

#### Example Response (Error: Rating Not Found)

```json
{
    "status": "error",
    "timestamp": 1747940300000,
    "code": 404,
    "message": "Rating does not exist for this user and product."
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747940400000,
    "code": 422,
    "message": "User ID is required and must be an integer."
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747940500000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

---

### deleteRetailer Endpoint

The `deleteRetailer` endpoint allows an admin to delete a retailer from the database by their `retailer_id`. The retailer will only be deleted if they exist. If the retailer does not exist, an error will be returned.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter     | Description                                      | Required |
|---------------|--------------------------------------------------|----------|
| `type`        | The request type: Must be set to `deleteRetailer`| Yes      |
| `apikey`      | The API key of the admin performing the request  | Yes      |
| `retailer_id` | The ID of the retailer to delete (integer)       | Yes      |
> **Note:**  
> All price entries for this retailer are deleted from the `Supplied_By` table before the retailer itself is deleted.

#### Example Request

```json
{
    "type": "deleteRetailer",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "retailer_id": 11
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747941000000,
    "code": 200,
    "message": "Retailer deleted successfully",
    "data": {
        "retailer_id": 11
    }
}
```

#### Example Response (Error: Retailer Not Found)

```json
{
    "status": "error",
    "timestamp": 1747941100000,
    "code": 404,
    "message": "Retailer does not exist."
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747941200000,
    "code": 422,
    "message": "Retailer ID is required and must be an integer."
}
```

#### Example Response (Error: Not Admin)

```json
{
    "status": "error",
    "timestamp": 1747941300000,
    "code": 403,
    "message": "User type (Customer) not allowed to use this request"
}
```

### editProduct Endpoint

The `editProduct` endpoint allows an admin to update the details of an existing product. The product is identified by its `product_id`. Only the fields provided in the request will be updated; fields not included in the request will remain unchanged. If the product name is being updated, it must be unique (no other product can have the same name). All fields are validated according to the same rules as `AddNewProduct`. 

**Important note: You cannot edit the product's price through this enpdpoint: Use the `QuickEditProductPrice` endpoint for that.**

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter      | Description                                      | Required |
|----------------|--------------------------------------------------|----------|
| `type`         | The request type: Must be set to `editProduct`   | Yes      |
| `apikey`       | The API key of the admin performing the request  | Yes      |
| `product_id`   | The ID of the product to update (integer)        | Yes      |
| `name`         | The new product name (max 100 characters, unique)| No       |
| `description`  | The new product description                      | No       |
| `image_url`    | The new product image URL (max 255 characters)   | No       |
| `category`     | The new product category (max 100 characters)    | No       |

> **Note:**  
> Only the fields provided in the request will be updated.  If the product name is being updated, it must not already exist for another product. If no updatable fields are provided, the request will fail validation.

#### Validation Rules

- **name**: Max 100 characters, must be unique (no other product with the same name).
- **description**: No length limit.
- **image_url**: Max 255 characters.
- **category**: Max 100 characters.

#### Example Request (Update Name and Category)

```json
{
    "type": "editProduct",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "product_id": 57,
    "name": "Super Widget Pro",
    "category": "Premium Widgets"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747950000000,
    "code": 200,
    "message": "Product updated successfully",
    "data": {
        "product_id": 57
    }
}
```

#### Example Response (Error: Product Not Found)

```json
{
    "status": "error",
    "timestamp": 1747950100000,
    "code": 404,
    "message": "Product does not exist."
}
```

#### Example Response (Error: Name Already Exists)

```json
{
    "status": "error",
    "timestamp": 1747950200000,
    "code": 409,
    "message": "Product name already exists. Please use a different name."
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747950300000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "name": "Product name must be at most 100 characters.",
        "image_url": "Image URL must be at most 255 characters."
    }
}
```

#### Example Response (Error: No Fields Provided)

```json
{
    "status": "error",
    "timestamp": 1747950400000,
    "code": 422,
    "message": "No fields provided to update."
}
```



---

## API Endpoints (CUSTOMER)

### getAllCategories Endpoint

The `getAllCategories` endpoint allows any authenticated user (Admin or Customer) to retrieve a list of all unique product categories from the `Product` table. This is typically used to populate a dropdown for category filtering on the products page.

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `getAllCategories` | Yes      |
| `apikey`    | The API key of the user performing the request   | Yes      |

#### Example Request

```json
{
    "type": "getAllCategories",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747994683000,
    "code": 200,
    "message": "All categories fetched successfully",
    "data": [
        "Accessories",
        "Audio",
        "Computers",
        "Electronics",
        "Fitness"
    ]
}
```

#### Example Response (Error: Not Authenticated)

```json
{
    "status": "error",
    "timestamp": 1747951100000,
    "code": 401,
    "message": "API key is required to authenticate user"
}
```

#### Example Response (Error: Invalid API Key)

```json
{
    "status": "error",
    "timestamp": 1747951200000,
    "code": 401,
    "message": "Invalid API key or User not found"
}
```

### getAllProducts Endpoint (for Customer)

The `getAllProducts` endpoint allows a customer to retrieve a list of products for display on the Products and Top-rated pages. The API performs all filtering, sorting, and aggregation, returning each product's image, title, average rating, cheapest price, and the retailer's name and ID for that price. Products with no ratings or prices are handled according to the request parameters.

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter                | Description                                                                                  | Required | Default   |
|--------------------------|----------------------------------------------------------------------------------------------|----------|-----------|
| `type`                   | The request type: Must be set to `getAllProducts`                                           | Yes      |           |
| `apikey`                 | The API key of the customer performing the request                                          | Yes      |           |
| `name`                   | Fuzzy search for product name (uses SQL LIKE)                                               | No       |           |
| `category`               | Filter by product category                                                                  | No       |           |
| `sort_by`                | Sort order: `price_asc`, `price_desc`, `name_asc`, `name_desc`, `rating_asc`, `rating_desc` | No       | `name_asc`|
| `include_no_price`       | Include products with no price (`true` or `false`)                                          | No       | `true`    |
| `include_no_rating`      | Include products with no ratings (`true` or `false`)                                        | No       | `true`    |
| `filter_by`              | Object with filter options (see below)                                                      | No       |           |
| `filter_by.minimum_average_rating` | Only include products with average rating above this float value                   | No       |           |
| `limit`                  | Maximum number of products to return                                                        | No       |           |

> **Note:**  
> If `filter_by.minimum_average_rating` is specified, products with no rating or price are excluded.
> If `include_no_price` or `include_no_rating` is set to `false`, products missing those values are excluded.
> Sorting by price or rating will always place products with no price/rating at the end of the list.

#### Response Fields

Each product in the response array includes:

| Field           | Description                                      |
|-----------------|--------------------------------------------------|
| `product_id`    | The product's unique ID (integer)                |
| `title`         | The product's name                               |
| `image_url`     | The product's image URL                          |
| `category`      | The product's category                           |
| `average_rating`| The product's average rating (float, 1 decimal) or `null` if no ratings |
| `cheapest_price`| The cheapest price for this product (float, 2 decimals) or `null` if no prices |
| `retailer_id`   | The retailer ID offering the cheapest price, or `null` if no prices |
| `retailer_name` | The retailer's name for the cheapest price, or `null` if no prices |

---

#### Example Request (Simple: All Products)

```json
{
    "type": "getAllProducts",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747995000000,
    "code": 200,
    "message": "All products fetched successfully",
    "data": [
        {
            "product_id": 1,
            "title": "Super Widget",
            "image_url": "https://example.com/widget.jpg",
            "category": "Widgets",
            "average_rating": 4.5,
            "cheapest_price": 49.99,
            "retailer_id": 5,
            "retailer_name": "Retailer C"
        },
        {
            "product_id": 2,
            "title": "Mega Gadget",
            "image_url": "https://example.com/gadget.jpg",
            "category": "Gadgets",
            "average_rating": null,
            "cheapest_price": null,
            "retailer_id": null,
            "retailer_name": null
        }
    ]
}
```

---

#### Example Request (With Filtering and Sorting)

```json
{
    "type": "getAllProducts",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "sort_by": "price_asc",
    "include_no_price": false,
    "include_no_rating": true,
    "limit": 5
}
```

---

#### Example Request (Top Rated Only)

```json
{
    "type": "getAllProducts",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "filter_by": {
        "minimum_average_rating": 4.0
    },
    "sort_by": "rating_desc"
}
```

---

#### Example Request (Fuzzy Name Search)

```json
{
    "type": "getAllProducts",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "name": "Widget"
}
```

---

#### Example Response (Error: Not Authenticated)

```json
{
    "status": "error",
    "timestamp": 1747995100000,
    "code": 401,
    "message": "API key is required to authenticate user"
}
```

#### Example Response (Error: Invalid API Key)

```json
{
    "status": "error",
    "timestamp": 1747995200000,
    "code": 401,
    "message": "Invalid API key or User not found"
}
```