# MogRex Transaction Management API

A comprehensive Laravel-based transaction management system built for the Morgex Software Engineer assessment. This API provides secure user authentication and robust transaction processing with advanced filtering capabilities.

## Base URL

```
https://mogrex-assessment.onrender.com
```

## Authentication

This API uses **Bearer Token** authentication. Include the token in the Authorization header:

```
Authorization: Bearer {your-token-here}
```

Tokens are obtained through the login endpoint and must be included in all authenticated requests.

---

## Authentication Endpoints

### Register User

Creates a new user account and returns authentication details.

**Endpoint:** `POST /api/v1/register`

**Headers:**

```
Accept: application/json
Content-Type: application/json
```

**Request Body:**

```json
{
    "name": "John Doe",
    "email": "john.doe@example.com",
    "password": "123456789A",
    "password_confirmation": "123456789A"
}
```

**Success Response:** `201 Created`

```json
{
    "message": "User registered successfully",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john.doe@example.com"
    },
    "token": "1|AbCdEf123456..."
}
```

---

### Login User

Authenticates a user and returns an access token.

**Endpoint:** `POST /api/v1/login`

**Headers:**

```
Accept: application/json
Content-Type: application/json
```

**Request Body:**

```json
{
    "email": "john.doe@example.com",
    "password": "123456789A"
}
```

**Success Response:** `200 OK`

```json
{
    "message": "Login successful",
    "token": "1|AbCdEf123456...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john.doe@example.com"
    }
}
```

---

### Get User Profile

Retrieves the authenticated user's profile information.

**Endpoint:** `GET /api/user`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Success Response:** `200 OK`

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "john.doe@example.com",
    "email_verified_at": null,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

---

### Logout User

Invalidates the current authentication token.

**Endpoint:** `POST /api/v1/logout`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Success Response:** `200 OK`

```json
{
    "message": "Logged out successfully"
}
```

---

## Transaction Management

### Create Transaction

Creates a new credit or debit transaction. Supports idempotency to prevent duplicate transactions.

**Endpoint:** `POST /api/v1/transactions`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
Idempotency-Key: {unique-key}
Content-Type: application/json
```

**Request Body:**

```json
{
    "amount": 1000.00,
    "type": "credit"
}
```

**Parameters:**

- `amount` (number, required): Transaction amount (positive number)
- `type` (string, required): Transaction type - either "credit" or "debit"

**Success Response:** `201 Created`

```json
{
    "message": "Transaction created successfully",
    "transaction": {
        "id": "TXN-ABC123DEF456",
        "amount": 1000.00,
        "type": "credit",
        "status": "pending",
        "user_id": 1,
        "created_at": "2024-01-15T10:30:00.000000Z",
        "updated_at": "2024-01-15T10:30:00.000000Z"
    }
}
```

---

### Get User Balance

Retrieves the current balance for the authenticated user.

**Endpoint:** `GET /api/v1/balance`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Success Response:** `200 OK`

```json
{
    "balance": 5000.00,
    "currency": "USD",
    "last_updated": "2024-01-15T10:30:00.000000Z"
}
```

---

### List All Transactions

Retrieves all transactions for the authenticated user with optional filtering and pagination.

**Endpoint:** `GET /api/v1/transactions`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Query Parameters:**

- `status` (string, optional): Filter by status - "pending", "processed", "failed"
- `type` (string, optional): Filter by type - "credit", "debit"
- `from_date` (date, optional): Filter from date (YYYY-MM-DD format)
- `to_date` (date, optional): Filter to date (YYYY-MM-DD format)
- `sort_by` (string, optional): Sort by field - "amount", "created_at"
- `sort_order` (string, optional): Sort order - "asc", "desc"
- `page` (integer, optional): Page number for pagination
- `per_page` (integer, optional): Number of items per page

**Success Response:** `200 OK`

```json
{
    "data": [
        {
            "id": "TXN-ABC123DEF456",
            "amount": 1000.00,
            "type": "credit",
            "status": "processed",
            "user_id": 1,
            "created_at": "2024-01-15T10:30:00.000000Z",
            "updated_at": "2024-01-15T10:30:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 25,
        "last_page": 2
    }
}
```

---

### Get Transaction by ID

Retrieves a specific transaction by its unique identifier.

**Endpoint:** `GET /api/v1/transactions/{transaction_id}`

**Headers:**

```
Accept: application/json
Authorization: Bearer {token}
```

**Path Parameters:**

- `transaction_id` (string, required): The unique transaction identifier

**Success Response:** `200 OK`

```json
{
    "id": "TXN-ABC123DEF456",
    "amount": 1000.00,
    "type": "credit",
    "status": "processed",
    "user_id": 1,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

---

## Advanced Query Examples

### Get Processed Credit Transactions (Newest First)

```
GET /api/v1/transactions?status=processed&type=credit&sort_order=desc
```

### Get Transactions from Date Range with Pagination

```
GET /api/v1/transactions?from_date=2024-04-01&to_date=2024-04-30&per_page=20&page=1
```

### Get Failed Transactions Sorted by Amount

```
GET /api/v1/transactions?status=failed&sort_by=amount&sort_order=desc
```

---

## Error Responses

All endpoints return structured error responses:

**Validation Error:** `422 Unprocessable Entity`

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password field is required."]
    }
}
```

**Authentication Error:** `401 Unauthorized`

```json
{
    "message": "Unauthenticated."
}
```

**Not Found Error:** `404 Not Found`

```json
{
    "message": "Transaction not found."
}
```

**Server Error:** `500 Internal Server Error`

```json
{
    "message": "An error occurred while processing your request."
}
```

---

## Important Features

### Idempotency

All transaction creation requests support idempotency keys to prevent duplicate transactions. Include a unique `Idempotency-Key` header with your request.

### Queue Processing

Transactions are processed asynchronously using Laravel's queue system for optimal performance and reliability.

### Security

- All endpoints require proper authentication except registration and login
- Passwords are securely hashed using Laravel's built-in security features
- API tokens are managed using Laravel Sanctum

### Rate Limiting

API requests may be subject to rate limiting to ensure system stability and fair usage.

---

## Development Notes

- Built with Laravel 10.x and PHP 8.2+
- Uses Laravel Sanctum for API authentication
- Implements comprehensive input validation
- Supports real-time transaction processing with queue workers
- Deployed on Render with Docker containerization

For technical implementation details, refer to the project's README.md file.
