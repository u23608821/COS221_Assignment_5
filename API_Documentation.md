# API Documentation

Our API provides functionality for our price comparison website called "Prick `n Price". It integrates front-end development with our back-end database.

## Table of Contents
- [API Documentation](#api-documentation)
  - [Table of Contents](#table-of-contents)
  - [Authentication](#authentication)
  - [Request Format](#request-format)
  - [Response Format](#response-format)
  - [API Endpoints (USER)](#api-endpoints-user)
    - [Test Endpoint](#test-endpoint)
    - [Register Endpoint](#register-endpoint)
    - [Login Endpoint](#login-endpoint)
  - [API Endpoints (ADMIN)](#api-endpoints-admin)
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
    - [getMyDetails Endpoint](#getmydetails-endpoint)
    - [updateMyDetails Endpoint](#updatemydetails-endpoint)
    - [getMyReviews Endpoint](#getmyreviews-endpoint)
    - [writeReview Endpoint](#writereview-endpoint)
    - [editMyReview Endpoint](#editmyreview-endpoint)
    - [deleteMyReview Endpoint](#deletemyreview-endpoint)
    - [getProductDetails Endpoint](#getproductdetails-endpoint)
    - [Example Response (Success: Product Only)](#example-response-success-product-only)
    - [Example Request and Response (Reviews Only)](#example-request-and-response-reviews-only)
    - [Example Request and Response (Retailers Only)](#example-request-and-response-retailers-only)
    - [addToWatchlist Endpoint](#addtowatchlist-endpoint)
    - [removeFromWatchlist Endpoint](#removefromwatchlist-endpoint)
    - [getMyWatchlist Endpoint](#getmywatchlist-endpoint)
    - [getReviewStats Endpoint](#getreviewstats-endpoint)


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

## API Endpoints (USER)

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

--

## API Endpoints (ADMIN)

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
> All ratings and price entries for this product are deleted from the `Rating` and `Supplied_By` tables, and all watchlist entries for this product in the `watchlist` table are deleted before the product itself is deleted.

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
> Only the fields provided in the request will be updated.  
> If `user_type` is changed, the user will be removed from their old type table and added to the new one.
> If the user is an admin and `position` or `salary` is provided, those fields will be updated in the `Admin_staff` table.
> If no updatable fields are provided, the request will fail validation. 
> The `email` field must be unique across all users. If the email already exists for another user, the request will fail.

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
> All ratings by this user are deleted from the `Rating` table, the user's watchlist products will be deleted from the `Watchlist` table, and the user is removed from the `Customer` or `Admin_staff` table before the user is deleted from the `User` table.

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

The `deleteRating` endpoint allows an admin to delete a rating (review) in two ways:
- By providing a `review_id` (the unique ID of the review to delete), **or**
- By providing both the `user_id` (the user who wrote the review) and `product_id` (the product being reviewed).

The endpoint will check that the review exists before deleting it. If the review does not exist, an error will be returned.

**Only users with the `Admin` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter    | Description                                      | Required |
|--------------|--------------------------------------------------|----------|
| `type`       | The request type: Must be set to `deleteRating`  | Yes      |
| `apikey`     | The API key of the admin performing the request  | Yes      |
| `review_id`  | The ID of the review to delete (integer)         | No*      |
| `user_id`    | The ID of the user who wrote the rating (integer)| No*      |
| `product_id` | The ID of the product being rated (integer)      | No*      |

> **Note:**  
> Either `review_id` **or** both `user_id` and `product_id` must be provided to identify the review to delete.

#### Example Request (By review_id)

```json
{
    "type": "deleteRating",
    "apikey": "FJihZZGK+5LOEVBX14JhkCCknJ6buHcrpJ/EKQpE1dA=",
    "review_id": 354
}
```

#### Example Request (By user_id and product_id)

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
        "review_id": 354,
        "user_id": 10002,
        "product_id": 57
    }
}
```

#### Example Response (Error: Review Not Found)

```json
{
    "status": "error",
    "timestamp": 1747940300000,
    "code": 404,
    "message": "Rating does not exist for this review ID."
}
```
or
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
    "message": "Either review_id or both user_id and product_id are required."
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

#### Example Request (Fuzzy Name Search)

```json
{
    "type": "getAllProducts",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "name": "Widget"
}
```

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

### getMyDetails Endpoint

The `getMyDetails` endpoint allows a customer to retrieve all their user and customer information. The user is identified by their API key. All fields from both the `User` and `Customer` tables are returned, except for sensitive fields (password, salt, apikey).

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `getMyDetails`  | Yes      |
| `apikey`    | The API key of the customer making the request   | Yes      |

#### Example Request

```json
{
    "type": "getMyDetails",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747998613000,
    "code": 200,
    "message": "Customer details fetched successfully",
    "data": {
        "id": 9999,
        "name": "Pieter",
        "surname": "Wenning",
        "phone_number": "0726206863",
        "email": "pieterwenning2@gmail.com",
        "street_number": "447A",
        "street_name": "Strubenkop st",
        "suburb": "Lynnwood",
        "city": null,
        "zip_code": "0081",
        "user_type": "Customer",
        "user_id": 9999
    }
}
```

#### Example Response (Error: User Not Found)

```json
{
    "status": "error",
    "timestamp": 1747998700000,
    "code": 404,
    "message": "User does not exist."
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1747998800000,
    "code": 422,
    "message": "API key is required to authenticate user"
}
```

#### Example Response (Error: Not Customer)

```json
{
    "status": "error",
    "timestamp": 1747996300000,
    "code": 403,
    "message": "User type (Admin) not allowed to use this request"
}
```

### updateMyDetails Endpoint

The `updateMyDetails` endpoint allows a customer to update their own details. Only the fields provided in the request will be updated; fields not included will remain unchanged. All provided fields are validated. If any provided field fails validation, no changes are made. If the email is provided, it must be unique (not used by another user). The `user_type` cannot be changed through this endpoint. Only admins can change the `user_type` of a user using their own `editUser` endpoint.

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Validation Rules

- **name**: Only letters, max 50 characters.
- **surname**: Only letters, max 50 characters.
- **phone_number**: Exactly 10 digits without spaces (e.g., `0726206863`).
- **email**: Valid email, max 100 characters, must be unique.
- **password**: At least 8 characters, must include upper and lower case letters, a number, and a special character.
- **street_number**: Max 10 characters.
- **street_name**: Only letters and spaces, max 100 characters.
- **suburb**: Only letters and spaces, max 100 characters.
- **city**: Only letters and spaces, max 100 characters.
- **zip_code**: Max 5 characters.

#### Request Parameters

| Parameter        | Description                        | Required |
|------------------|------------------------------------|----------|
| `type`           | The request type: Must be `updateMyDetails` | Yes      |
| `apikey`         | The API key of the customer        | Yes      |
| `name`           | The user's name (only letters, max 50 chars) | No       |
| `surname`        | The user's surname (only letters, max 50 chars) | No       |
| `phone_number`   | The user's phone number (exactly 10 digits) | No       |
| `email`          | The user's email address (valid, max 100 chars, unique) | No       |
| `password`       | The user's password (min 8 chars, upper/lower/number/special char) | No |
| `street_number`  | The user's street number (max 10 chars) | No       |
| `street_name`    | The user's street name (only letters and spaces, max 100 chars) | No       |
| `suburb`         | The user's suburb (only letters and spaces, max 100 chars) | No       |
| `city`           | The user's city (only letters and spaces, max 100 chars) | No       |
| `zip_code`       | The user's zip code (max 5 chars) | No       |

> **Note:**  
> Only the fields provided in the request will be updated.  
> If any provided field fails validation, no changes will be made and an error will be returned.  
> If the email is already used by another user, the request will fail.

#### Example Request (Update Address, Email and Phone number)

```json
{
    "type": "updateMyDetails",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "name": "Pieter",
    "email": "pieterwenning2@gmail.com",
    "phone_number": "0726206863",
    "street_number": "447A",
    "street_name": "Strubenkop st",
    "suburb": "Lynnwood",
    "zip_code": "0081"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747998575000,
    "code": 200,
    "message": "Customer details updated successfully",
    "data": {
        "user_id": 9999
    }
}
```

#### Example Response (Error: Duplicate Email)

```json
{
    "status": "error",
    "timestamp": 1747998668000,
    "code": 409,
    "message": "Email already exists for another user."
}
```

#### Example Response (Error: Validation Failed)

```json
{
    "status": "error",
    "timestamp": 1747998718000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "phone_number": "Phone number must be exactly 10 digits",
        "zip_code": "Zip code must be 5 characters or less"
    }
}
```

#### Example Response (Error: Not Customer)

```json
{
    "status": "error",
    "timestamp": 1747997400000,
    "code": 403,
    "message": "User type (Admin) not allowed to use this request"
}
```

### getMyReviews Endpoint

The `getMyReviews` endpoint allows a customer to retrieve all of the reviews that they have written. Each review includes the review details (id, score, description, last_updated), as well as the product's name, image, cheapest price, and the retailer offering that price.

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `getMyReviews`  | Yes      |
| `apikey`    | The API key of the customer making the request   | Yes      |

#### Example Request

```json
{
    "type": "getMyReviews",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1747999953000,
    "code": 200,
    "message": "My reviews fetched successfully",
    "data": [
        {
            "review_id": 354,
            "score": 2,
            "description": "Product not as decribed! I wish I didn't order this :(",
            "last_updated": "2025-05-23 13:32:18",
            "product_id": 50,
            "product_name": "External SSD",
            "image_url": "https://images.pexels.com/photos/2644597/pexels-photo-2644597.jpeg?auto=compress&cs=tinysrgb&h=350",
            "cheapest_price": 451.04,
            "retailer_id": 5,
            "retailer_name": "Flashpoint"
        },
        {
            "review_id": 353,
            "score": 5,
            "description": "Great product, highly recommend!",
            "last_updated": "2025-05-23 13:31:15",
            "product_id": 49,
            "product_name": "Storage Containers",
            "image_url": "https://images.pexels.com/photos/32151281/pexels-photo-32151281.jpeg?auto=compress&cs=tinysrgb&h=350",
            "cheapest_price": 85.52,
            "retailer_id": 7,
            "retailer_name": "Jayo"
        }
    ]
}
```

#### Example Response (Error: Not Authenticated)

```json
{
    "status": "error",
    "timestamp": 1748000100000,
    "code": 401,
    "message": "API key is required to authenticate user"
}
```

### writeReview Endpoint

The `writeReview` endpoint allows a customer to add a review for a product. If the customer has already reviewed this product, the review will be updated instead of creating a new one. The customer is identified by their API key. All fields are validated before insertion or update. If any field is invalid, no review is added or updated.

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter     | Description                                      | Required |
|---------------|--------------------------------------------------|----------|
| `type`        | The request type: Must be set to `writeReview`   | Yes      |
| `apikey`      | The API key of the customer making the request   | Yes      |
| `product_id`  | The ID of the product being reviewed (integer)   | Yes      |
| `score`       | The review score (integer, 1-5)                  | Yes      |
| `description` | The review text (min 10 characters)              | Yes      |

> **Note:**
> If the customer **has not reviewed** this product before, a new review is created.
> If the customer **already has a review** for this product, the review is updated with the passed in score and description.

#### Example Request (Add or Update Review)

```json
{
    "type": "writeReview",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "product_id": 49,
    "score": 5,
    "description": "Great product, highly recommend!"
}
```

#### Example Response (Review Added)

```json
{
    "status": "success",
    "timestamp": 1747999879000,
    "code": 201,
    "message": "Review added successfully",
    "data": {
        "review_id": 353,
        "customer_id": 9999,
        "product_id": 49
    }
}
```

#### Example Response (Review Updated)

```json
{
    "status": "success",
    "timestamp": 1747999888000,
    "code": 200,
    "message": "Review updated successfully",
    "data": {
        "review_id": 353,
        "customer_id": 9999,
        "product_id": 49
    }
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1748000300000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "score": "Score must be an integer between 1 and 5.",
        "description": "Description must be at least 10 characters."
    }
}
```

#### Example Response (Error: Product Not Found)

```json
{
    "status": "error",
    "timestamp": 1748000400000,
    "code": 404,
    "message": "Product does not exist."
}
```

### editMyReview Endpoint

The `editMyReview` endpoint allows a customer to edit a review that they have written. The review can be identified by either the `review_id` or the `product_id` (the review must belong to the authenticated user). Only the fields provided will be updated. All provided fields are validated; if any field is invalid, no changes are made.

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter     | Description                                      | Required |
|---------------|--------------------------------------------------|----------|
| `type`        | The request type: Must be set to `editMyReview`  | Yes      |
| `apikey`      | The API key of the customer making the request   | Yes      |
| `review_id`   | The ID of the review to edit (integer)           | No*      |
| `product_id`  | The ID of the product reviewed (integer)         | No*      |
| `score`       | The new review score (integer, 1-5)              | No       |
| `description` | The new review text (min 10 characters)          | No       |

> **Note:**  
> Either `review_id` or `product_id` must be provided to identify the review.  
> At least one of `score` or `description` must be provided to update.

#### Example Request (Edit by review_id)

```json
{
    "type": "editMyReview",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "review_id": 101,
    "score": 4,
    "description": "Good product, but could be improved."
}
```

#### Example Request (Edit by product_id)

```json
{
    "type": "editMyReview",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "product_id": 7,
    "score": 3
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1748000500000,
    "code": 200,
    "message": "Review updated successfully",
    "data": {
        "review_id": 101,
        "customer_id": 9999,
        "product_id": 7
    }
}
```

#### Example Response (Error: Review Not Found)

```json
{
    "status": "error",
    "timestamp": 1748000600000,
    "code": 404,
    "message": "Review not found for this user."
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1748000700000,
    "code": 422,
    "message": "Parameter validation failed!",
    "data": {
        "score": "Score must be an integer between 1 and 5."
    }
}
```

### deleteMyReview Endpoint

The `deleteMyReview` endpoint allows a customer to delete a review that they have written. The review can be identified by either `review_id` or `product_id` (the review must belong to the authenticated user).

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter     | Description                                      | Required |
|---------------|--------------------------------------------------|----------|
| `type`        | The request type: Must be set to `deleteMyReview`| Yes      |
| `apikey`      | The API key of the customer making the request   | Yes      |
| `review_id`   | The ID of the review to delete (integer)         | No*      |
| `product_id`  | The ID of the product reviewed (integer)         | No*      |

> **Note:**  
> Either `review_id` or `product_id` must be provided to identify the review.

#### Example Request (By review_id)

```json
{
    "type": "deleteMyReview",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "review_id": 354
}
```

#### Example Request (By product_id)

```json
{
    "type": "deleteMyReview",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "product_id": 7
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1748000143000,
    "code": 200,
    "message": "Review deleted successfully",
    "data": {
        "review_id": 354,
        "customer_id": 9999,
        "product_id": 50
    }
}
```

#### Example Response (Error: Review Not Found)

```json
{
    "status": "error",
    "timestamp": 1748000900000,
    "code": 404,
    "message": "Review not found for this user."
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1748001000000,
    "code": 422,
    "message": "Review ID or Product ID is required."
}
```

### getProductDetails Endpoint

The `getProductDetails` endpoint allows a customer to retrieve information about a specific product. You can control which information is returned using the `return` parameter.

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter     | Description                                      | Required | Default   |
|---------------|--------------------------------------------------|----------|-----------|
| `type`        | The request type: Must be set to `getProductDetails` | Yes      |           |
| `apikey`      | The API key of the customer making the request   | Yes      |           |
| `product_id`  | The ID of the product to fetch (integer)         | Yes      |           |
| `return`      | What to return: `All`, `Product`, `Retailers`, or `Reviews` | No | `All` |
| `sort_reviews`| Sort reviews: `time_ASC`, `time_DESC`, `score_ASC`, `score_DESC`, `name_ASC`, `name_DESC` | No | `time_DESC` |
| `sort_retailers` | Sort retailers: `name_ASC`, `name_DESC`, `price_ASC`, `price_DESC` | No | `price_ASC` |
| `filter_reviews_by_score` | Only return reviews with this score (1-5) | No |           |

> **Note:**  
> If `return` is `"All"` (default), all product info, retailers, and reviews are returned.  
> If `return` is `"Product"`, only product info (including cheapest price/retailer and average review) is returned.  
> If `return` is `"Retailers"`, only the retailers/prices array is returned.  
> If `return` is `"Reviews"`, only the reviews array is returned.  
> Sorting and filtering options only affect the relevant arrays if they are included in the response.

#### Response Fields

| Field                  | Description                                                      |
|------------------------|------------------------------------------------------------------|
| `id`                   | Product ID (integer)                                             |
| `name`                 | Product name                                                     |
| `description`          | Product description                                              |
| `image_url`            | Product image URL                                                |
| `category`             | Product category                                                 |
| `cheapest_price`       | The lowest price for this product (float, 2 decimals) or `null`  |
| `cheapest_retailer`    | Name of the retailer offering the cheapest price or `null`       |
| `cheapest_retailer_id` | ID of the retailer offering the cheapest price or `null`         |
| `average_review`       | Average review score for the product (float, 1 decimal) or `null`|
| `retailers`            | Array of all retailers offering this product (see below)         |
| `reviews`              | Array of all reviews for this product (see below)                |

**Each retailer in `retailers`:**
| Field           | Description                    |
|-----------------|-------------------------------|
| `retailer_id`   | Retailer ID (integer)         |
| `retailer_name` | Retailer name                 |
| `price`         | Price offered by this retailer (float, 2 decimals) |

**Each review in `reviews`:**
| Field           | Description                    |
|-----------------|-------------------------------|
| `review_id`     | Review ID (integer)           |
| `customer_id`   | ID of the reviewer (integer)  |
| `customer_name` | Name of the reviewer          |
| `score`         | Review score (integer, 1-5)   |
| `description`   | Review text                   |
| `updated_at`    | Last updated timestamp        |

#### Example Request and Response (All Details)
**Request Object**
```json
{
    "type": "getProductDetails",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "product_id": 50
}
```

**Response Object**
```json
{
    "status": "success",
    "timestamp": 1748001919000,
    "code": 200,
    "message": "Product details fetched successfully",
    "data": {
        "id": 50,
        "name": "External SSD",
        "description": "High-speed portable SSD with USB-C connectivity.",
        "Image_url": "https://images.pexels.com/photos/2644597/pexels-photo-2644597.jpeg?auto=compress&cs=tinysrgb&h=350",
        "category": "Storage",
        "cheapest_price": 451.04,
        "cheapest_retailer": "Flashpoint",
        "cheapest_retailer_id": 5,
        "average_review": 3.5,
        "retailers": [
            {
                "retailer_id": 5,
                "retailer_name": "Flashpoint",
                "price": 451.04
            },
            {
                "retailer_id": 4,
                "retailer_name": "Camido",
                "price": 470.04
            },
            {
                "retailer_id": 6,
                "retailer_name": "Talane",
                "price": 492.04
            },
            {
                "retailer_id": 8,
                "retailer_name": "Tanoodle",
                "price": 520.04
            },
            {
                "retailer_id": 1,
                "retailer_name": "Voonix",
                "price": 521.04
            },
            {
                "retailer_id": 7,
                "retailer_name": "Jayo",
                "price": 521.04
            }
        ],
        "reviews": [
            {
                "review_id": 357,
                "customer_id": 10004,
                "customer_name": "AdrianoCustomer",
                "score": 3,
                "description": "Cool product!! Doesn't work though. Had to return it.",
                "updated_at": "2025-05-23 14:04:34"
            },
            {
                "review_id": 355,
                "customer_id": 9999,
                "customer_name": "Pieter",
                "score": 4,
                "description": "Great product, highly recommend!",
                "updated_at": "2025-05-23 14:03:10"
            }
        ]
    }
}
```

### Example Response (Success: Product Only)

```json
{
    "status": "success",
    "timestamp": 1748002698000,
    "code": 200,
    "message": "Product details fetched successfully",
    "data": {
        "id": 50,
        "name": "External SSD",
        "description": "High-speed portable SSD with USB-C connectivity.",
        "Image_url": "https://images.pexels.com/photos/2644597/pexels-photo-2644597.jpeg?auto=compress&cs=tinysrgb&h=350",
        "category": "Storage",
        "cheapest_price": 451.04,
        "cheapest_retailer": "Flashpoint",
        "cheapest_retailer_id": 5,
        "average_review": 3.5
    }
}
```

### Example Request and Response (Reviews Only)

**Request Object**
```json
{
    "type": "getProductDetails",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "product_id": 50,
    "return": "Reviews",
    "sort_reviews": "score_DESC"
}
```

**Response Object**
```json
{
    "status": "success",
    "timestamp": 1748002799000,
    "code": 200,
    "message": "Reviews fetched successfully",
    "data": [
        {
            "review_id": 355,
            "customer_id": 9999,
            "customer_name": "Pieter",
            "score": 4,
            "description": "Great product, highly recommend!",
            "updated_at": "2025-05-23 14:03:10"
        },
        {
            "review_id": 357,
            "customer_id": 10004,
            "customer_name": "AdrianoCustomer",
            "score": 3,
            "description": "Cool product!! Doesn't work though. Had to return it.",
            "updated_at": "2025-05-23 14:04:34"
        }
    ]
}
```

### Example Request and Response (Retailers Only)

**Request Object**
```json
{
    "type": "getProductDetails",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "product_id": 50,
    "return": "Retailers",
    "sort_retailers": "name_DESC"
}
```

***Response Object**
```json
{
    "status": "success",
    "timestamp": 1748002942000,
    "code": 200,
    "message": "Retailers fetched successfully",
    "data": [
        {
            "retailer_id": 1,
            "retailer_name": "Voonix",
            "price": 521.04
        },
        {
            "retailer_id": 8,
            "retailer_name": "Tanoodle",
            "price": 520.04
        },
        {
            "retailer_id": 6,
            "retailer_name": "Talane",
            "price": 492.04
        },
        {
            "retailer_id": 7,
            "retailer_name": "Jayo",
            "price": 521.04
        },
        {
            "retailer_id": 5,
            "retailer_name": "Flashpoint",
            "price": 451.04
        },
        {
            "retailer_id": 4,
            "retailer_name": "Camido",
            "price": 470.04
        }
    ]
}
```

#### Example Response (Error: Product Not Found)

```json
{
    "status": "error",
    "timestamp": 1748002100000,
    "code": 404,
    "message": "Product does not exist."
}
```

#### Example Response (Error: Validation)

```json
{
    "status": "error",
    "timestamp": 1748002200000,
    "code": 422,
    "message": "Product ID is required and must be an integer."
}
```

#### Example Response (Error: Not Authenticated)

```json
{
    "status": "error",
    "timestamp": 1748002300000,
    "code": 401,
    "message": "API key is required to authenticate user"
}
```

#### Example Response (Error: Not Customer)

```json
{
    "status": "error",
    "timestamp": 1748002400000,
    "code": 403,
    "message": "User type (Admin) not allowed to use this request"
}
```

### addToWatchlist Endpoint

The `addToWatchlist` endpoint allows a customer to add a product to their watchlist.  
If the product is already in the watchlist, the API returns a success message with code 200 and does not add it again.

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter    | Description                                      | Required |
|--------------|--------------------------------------------------|----------|
| `type`       | The request type: Must be set to `addToWatchlist`| Yes      |
| `apikey`     | The API key of the customer making the request   | Yes      |
| `product_id` | The ID of the product to add (integer)           | Yes      |

#### Example Request

```json
{
    "type": "addToWatchlist",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "product_id": 50
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1748004000000,
    "code": 201,
    "message": "Product added to watchlist successfully",
    "data": {
        "product_id": 50
    }
}
```

#### Example Response (Already in Watchlist)

```json
{
    "status": "success",
    "timestamp": 1748004100000,
    "code": 200,
    "message": "Product already in watchlist",
    "data": {
        "product_id": 50
    }
}
```

#### Example Response (Error: Product Not Found)

```json
{
    "status": "error",
    "timestamp": 1748004200000,
    "code": 404,
    "message": "Product does not exist."
}
```

### removeFromWatchlist Endpoint

The `removeFromWatchlist` endpoint allows a customer to remove a product from their watchlist.  
If the product is not in the watchlist, the API returns a success message with code 200 and does nothing.

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter    | Description                                      | Required |
|--------------|--------------------------------------------------|----------|
| `type`       | The request type: Must be set to `removeFromWatchlist` | Yes      |
| `apikey`     | The API key of the customer making the request   | Yes      |
| `product_id` | The ID of the product to remove (integer)        | Yes      |

#### Example Request

```json
{
    "type": "removeFromWatchlist",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "product_id": 50
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1748004300000,
    "code": 200,
    "message": "Product removed from watchlist successfully",
    "data": {
        "product_id": 50
    }
}
```

#### Example Response (Not in Watchlist)

```json
{
    "status": "success",
    "timestamp": 1748004400000,
    "code": 200,
    "message": "Product not in watchlist",
    "data": {
        "product_id": 50
    }
}
```

#### Example Response (Error: Product Not Found)

```json
{
    "status": "error",
    "timestamp": 1748004500000,
    "code": 404,
    "message": "Product does not exist."
}
```

### getMyWatchlist Endpoint

The `getMyWatchlist` endpoint allows a customer to retrieve all products in their watchlist. Each product includes the same details as returned by the `getAllProducts` endpoint for customers (product ID, title, image, category, average rating, cheapest price, and retailer info).

**Only users with the `Customer` user_type (validated by their API key) can use this endpoint.**

#### Request Parameters

| Parameter   | Description                                      | Required |
|-------------|--------------------------------------------------|----------|
| `type`      | The request type: Must be set to `getMyWatchlist`| Yes      |
| `apikey`    | The API key of the customer making the request   | Yes      |

#### Example Request

```json
{
    "type": "getMyWatchlist",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef"
}
```

#### Example Response (Success)

```json
{
    "status": "success",
    "timestamp": 1748004600000,
    "code": 200,
    "message": "Watchlist fetched successfully",
    "data": [
        {
            "product_id": 50,
            "title": "External SSD",
            "image_url": "https://images.pexels.com/photos/2644597/pexels-photo-2644597.jpeg?auto=compress&cs=tinysrgb&h=350",
            "category": "Storage",
            "average_rating": 3.5,
            "cheapest_price": 451.04,
            "retailer_id": 5,
            "retailer_name": "Flashpoint"
        },
        {
            "product_id": 49,
            "title": "Storage Containers",
            "image_url": "https://images.pexels.com/photos/32151281/pexels-photo-32151281.jpeg?auto=compress&cs=tinysrgb&h=350",
            "category": "Home",
            "average_rating": 4.2,
            "cheapest_price": 85.52,
            "retailer_id": 7,
            "retailer_name": "Jayo"
        }
    ]
}
```

#### Example Response (Error: Not Authenticated)

```json
{
    "status": "error",
    "timestamp": 1748004700000,
    "code": 401,
    "message": "API key is required to authenticate user"
}
```

#### Example Response (Error: Invalid API Key)

```json
{
    "status": "error",
    "timestamp": 1748004800000,
    "code": 401,
    "message": "Invalid API key or User not found"
}
```

### getReviewStats Endpoint

The `getReviewStats` endpoint provides a comprehensive set of review-related statistics for all products and users on the platform. It is designed for analytics dashboards, admin insights, and visualizations. Both Admin and Customer users (validated by their API key) can use this endpoint.

> **Performance Note:**  
> Requesting all statistics at once (`return: "All"`) is computationally expensive and may be slow. For best performance, use the `return` parameter to request only the specific statistic(s) you need.

#### Request Parameters

| Parameter    | Description                                                                 | Required | Default |
|--------------|-----------------------------------------------------------------------------|----------|---------|
| `type`       | The request type: Must be set to `getReviewStats`                           | Yes      |         |
| `apikey`     | The API key of the user making the request                                  | Yes      |         |
| `return`     | Which statistic to return (see below). If omitted, returns all statistics.  | No       | `All`   |
| `min_reviews`| Minimum number of reviews for "top_rated_with_min_reviews" (integer, >=1)   | No       | 2       |

##### Allowed values for `return`:
- `All` (default): Returns all statistics below in a single response.
- `star_counts`
- `starAverage_Counts`
- `total_reviews`
- `pie_percentages`
- `average_review`
- `best_worst_products`
- `most_reviewed_products`
- `least_reviewed_products`
- `most_active_reviewers`
- `review_growth`
- `products_no_reviews`
- `review_length_stats`
- `avg_score_per_category`
- `percent_products_with_reviews`
- `top_rated_with_min_reviews`
- `number_of_products`

#### Returned Statistics (with explanations)

- **star_counts**:  
  The number of individual reviews for each star rating (1-5).  
  *Example:* `{ "1": 3, "2": 7, "3": 12, "4": 20, "5": 58 }`  
  *Use case:* Bar chart of all review scores.

- **starAverage_Counts**:  
  The number of products whose average review falls into each star "bin":  
  - 1.0–1.9 → 1 star  
  - 2.0–2.9 → 2 stars  
  - 3.0–3.9 → 3 stars  
  - 4.0–4.9 → 4 stars  
  - 5.0     → 5 stars  
  *Example:* `{ "1": 2, "2": 5, "3": 10, "4": 18, "5": 7 }`  
  *Use case:* Bar or pie chart showing product quality distribution.

- **total_reviews**:  
  The total number of individual reviews in the system.  
  *Example:* `100`  
  *Use case:* Overall review volume.

- **pie_percentages**:  
  The percentage of products whose average review falls into each star "bin" (see above).  
  *Example:* `{ "1": 5.0, "2": 12.5, "3": 25.0, "4": 45.0, "5": 12.5 }`  
  *Use case:* Pie chart of product quality.

- **average_review**:  
  The average of all products' average review ratings (rounded to 1 decimal).  
  *Example:* `4.2`  
  *Use case:* Overall product satisfaction.

- **best_worst_products**:  
  The product(s) with the highest and lowest average review rating (among products with at least one review).  
  *Example:*  
  ```json
  {
    "best_products": [
      {
        "product_id": 12,
        "title": "Super Widget",
        "image_url": "...",
        "category": "Widgets",
        "average_rating": 4.9,
        "review_count": 15
      }
    ],
    "worst_products": [
      {
        "product_id": 7,
        "title": "Bad Gadget",
        "image_url": "...",
        "category": "Gadgets",
        "average_rating": 1.2,
        "review_count": 8
      }
    ]
  }
  ```
  *Use case:* Highlight best/worst products.

- **most_reviewed_products**:  
  Product(s) with the highest number of reviews (with product info).  
  *Example:*  
  ```json
  [
    {
      "product_id": 5,
      "title": "Popular Product",
      "image_url": "...",
      "category": "Electronics",
      "review_count": 32
    }
  ]
  ```
  *Use case:* Show most discussed products.

- **least_reviewed_products**:  
  Product(s) with the lowest (but >0) number of reviews (with product info).  
  *Example:*  
  ```json
  [
    {
      "product_id": 8,
      "title": "Rarely Reviewed",
      "image_url": "...",
      "category": "Home",
      "review_count": 1
    }
  ]
  ```
  *Use case:* Find products needing more feedback.

- **most_active_reviewers**:  
  User(s) who have written the most reviews (with user info: id, name, review count).  
  *Example:*  
  ```json
  [
    {
      "user_id": 10001,
      "name": "Alice",
      "review_count": 25
    }
  ]
  ```
  *Use case:* Identify top contributors.

- **review_growth**:  
  Number of reviews per month for the last 12 months (for a line chart).  
  *Example:*  
  ```json
  [
    { "month": "2024-06", "review_count": 12 },
    { "month": "2024-07", "review_count": 18 }
  ]
  ```
  *Use case:* Show review activity trends.

- **products_no_reviews**:  
  The count of products that have never been reviewed.  
  *Example:* `7`  
  *Use case:* Identify products needing first reviews.

- **review_length_stats**:  
  Statistics about review description lengths: average, min, max, and median.  
  *Example:*  
  ```json
  {
    "average_length": 54.2,
    "min_length": 10,
    "max_length": 200,
    "median_length": 52
  }
  ```
  *Use case:* Analyze review quality and engagement.

- **avg_score_per_category**:  
  Average review score per product category (rounded to 1 decimal).  
  *Example:*  
  ```json
  [
    { "category": "Electronics", "average_score": 4.3 },
    { "category": "Home", "average_score": 3.8 }
  ]
  ```
  *Use case:* Compare satisfaction across categories.

- **percent_products_with_reviews**:  
  Percentage of products that have at least one review (float, 1 decimal).  
  *Example:* `82.5`  
  *Use case:* Coverage insight for product reviews.

- **top_rated_with_min_reviews**:  
  The top-rated product(s) with at least X reviews (default X=2, can be set with `min_reviews`).  
  *Example:*  
  ```json
  [
    {
      "product_id": 15,
      "title": "Trusted Product",
      "image_url": "...",
      "category": "Electronics",
      "average_rating": 4.8,
      "review_count": 12
    }
  ]
  ```
  *Use case:* Highlight reliably top-rated products.

- **number_of_products**:  
  The total number of products in the database.  
  *Example:* `120`  
  *Use case:* General statistics and coverage.

---

#### Example Request (All Stats)

```json
{
    "type": "getReviewStats",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef"
}
```

#### Example Request (Only Pie Percentages)

```json
{
    "type": "getReviewStats",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "return": "pie_percentages"
}
```

#### Example Request (Top Rated Product With At Least 5 Reviews)

```json
{
    "type": "getReviewStats",
    "apikey": "c9efa15677a63c3932d5d62794a13ff9021d75aaf6ff6b8fb45b15ac4e6987ef",
    "return": "top_rated_with_min_reviews",
    "min_reviews": 5
}
```

#### Example Response (All Stats)

```json
{
    "status": "success",
    "timestamp": 1748006572000,
    "code": 200,
    "message": "Review statistics fetched successfully",
    "data": {
        "star_counts": {
            "1": 0,
            "2": 0,
            "3": 2,
            "4": 2,
            "5": 0
        },
        "starAverage_Counts": {
            "1": 0,
            "2": 0,
            "3": 2,
            "4": 1,
            "5": 0
        },
        "total_reviews": 4,
        "pie_percentages": {
            "1": 0,
            "2": 0,
            "3": 66.7,
            "4": 33.3,
            "5": 0
        },
        "average_review": 3.5,
        "best_products": [
            {
                "product_id": 28,
                "title": "Air Purifier",
                "image_url": "https://images.pexels.com/photos/3554239/pexels-photo-3554239.jpeg?auto=compress&cs=tinysrgb&h=350",
                "category": "Home Appliances",
                "average_rating": 4,
                "review_count": 1
            }
        ],
        "worst_products": [
            {
                "product_id": 49,
                "title": "Storage Containers",
                "image_url": "https://images.pexels.com/photos/32151281/pexels-photo-32151281.jpeg?auto=compress&cs=tinysrgb&h=350",
                "category": "Kitchen",
                "average_rating": 3,
                "review_count": 1
            }
        ],
        "most_reviewed_products": [
            {
                "product_id": 50,
                "title": "External SSD",
                "image_url": "https://images.pexels.com/photos/2644597/pexels-photo-2644597.jpeg?auto=compress&cs=tinysrgb&h=350",
                "category": "Storage",
                "review_count": 2
            }
        ],
        "least_reviewed_products": [
            {
                "product_id": 28,
                "title": "Air Purifier",
                "image_url": "https://images.pexels.com/photos/3554239/pexels-photo-3554239.jpeg?auto=compress&cs=tinysrgb&h=350",
                "category": "Home Appliances",
                "review_count": 1
            }
        ],
        "most_active_reviewers": [
            {
                "user_id": 10004,
                "name": "AdrianoCustomer",
                "review_count": 2
            }
        ],
        "review_growth": [
            {
                "month": "2025-05",
                "review_count": 4
            }
        ],
        "products_no_reviews": 46,
        "number_of_products": 49,
        "review_length_stats": {
            "average_length": 62.8,
            "min_length": 32,
            "max_length": 116,
            "median_length": 51.5
        },
        "avg_score_per_category": [
            {
                "category": "Home Appliances",
                "average_score": 4
            },
            {
                "category": "Kitchen",
                "average_score": 3
            },
            {
                "category": "Storage",
                "average_score": 3.5
            }
        ],
        "percent_products_with_reviews": 6.1,
        "top_rated_with_min_reviews": [
            {
                "product_id": 50,
                "title": "External SSD",
                "image_url": "https://images.pexels.com/photos/2644597/pexels-photo-2644597.jpeg?auto=compress&cs=tinysrgb&h=350",
                "category": "Storage",
                "average_rating": 3.5,
                "review_count": 2
            }
        ]
    }
}
```

#### Example Response (Single Stat)

```json
{
    "status": "success",
    "timestamp": 1748006100000,
    "code": 200,
    "message": "Review statistics fetched successfully",
    "data": {
        "pie_percentages": { "1": 5.0, "2": 12.5, "3": 25.0, "4": 45.0, "5": 12.5 }
    }
}
```

#### Example Response (Error: Not Authenticated)

```json
{
    "status": "error",
    "timestamp": 1748006200000,
    "code": 401,
    "message": "API key is required to authenticate user"
}
```

#### Example Response (Error: Not Authorised)

```json
{
    "status": "error",
    "timestamp": 1748006300000,
    "code": 403,
    "message": "User type (Unknown) not allowed to use this request"
}
```