# Laravel Assessment - Morgex Software Engineer Position

A comprehensive Laravel application built as part of the technical assessment for the Software Engineer position at Morgex.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
  - [Running with Docker](#running-with-docker)
  - [Running without Docker](#running-without-docker)
- [API Documentation](#api-documentation)
- [Environment Configuration](#environment-configuration)
- [Queue System](#queue-system)
- [Testing](#testing)
- [Deployment](#deployment)

## Features

- RESTful API endpoints
- Queue-based background processing
- Database migrations and seeders
- Input validation and error handling
- Authentication system
- Comprehensive API documentation

## Requirements

### For Docker Setup

- Docker
- Docker Compose
- Git

### For Local Setup

- PHP 8.2+
- Composer
- SQLite/MySQL/PostgreSQL
- Node.js & NPM (if using frontend assets)

## Installation

### Running with Docker

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd <project-directory>
   ```

2. **Build and run with Docker**

   ```bash
   # Build the Docker image
   docker build -t morgex-laravel-app .
   
   # Run the container
   docker run -p 8000:8000 morgex-laravel-app
   ```

3. **Alternative: Using Docker Compose (if available)**

   ```bash
   docker-compose up --build
   ```

4. **Access the application**
   - API Base URL: `http://localhost:8000`
   - Health Check: `http://localhost:8000/api/health` (if available)

### Running without Docker

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd <project-directory>
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Environment setup**

   ```bash
   # Copy environment file
   cp .env.example .env
   
   # Generate application key
   php artisan key:generate
   ```

4. **Database setup**

   ```bash
   # Run migrations
   php artisan migrate
   
   # Run seeders (if available)
   php artisan db:seed
   ```

5. **Start the development server**

   ```bash
   php artisan serve
   ```

6. **Start the queue worker (in a separate terminal)**

   ```bash
   php artisan queue:work --tries=3
   ```

7. **Access the application**
   - API Base URL: `http://localhost:8000`

## API Documentation

The application includes the following API endpoints. Import the provided Postman collection (`MogRex.postman_collection.json`) for complete API documentation and testing.

### Importing Postman Collection

1. Open Postman
2. Click "Import" button
3. Select the `MogRex.postman_collection.json` file
4. The collection will be imported with all endpoints and example requests

### Key Endpoints

The API provides a comprehensive transaction management system with the following endpoints:

#### Authentication Endpoints

- `POST /api/v1/register` - User registration
- `POST /api/v1/login` - User authentication
- `GET /api/user` - Get authenticated user details
- `POST /api/v1/logout` - User logout

#### Transaction Management

- `POST /api/v1/transactions` - Create a new transaction (credit/debit)
- `GET /api/v1/transactions` - List all transactions with filtering and pagination
- `GET /api/v1/transactions/{id}` - Get specific transaction by ID
- `GET /api/v1/balance` - Get current user balance

#### Advanced Filtering & Sorting

The transactions endpoint supports comprehensive filtering:

- Filter by status (`processed`, `failed`, `pending`)
- Filter by type (`credit`, `debit`)
- Date range filtering (`from_date`, `to_date`)
- Sorting by various fields (`amount`, `created_at`)
- Pagination support (`page`, `per_page`)

#### Special Features

- **Idempotency**: All transaction operations support idempotency keys
- **Bearer Token Authentication**: Secure API access using Laravel Sanctum
- **Queue Processing**: Background transaction processing
- **Comprehensive Filtering**: Advanced query capabilities for transactions

*Note: Refer to the imported Postman collection for detailed endpoint documentation, request/response examples, and authentication requirements.*

## Environment Configuration

Key environment variables to configure:

```env
APP_NAME="Morgex Laravel Assessment"
APP_ENV=local
APP_KEY=base64:your-app-key-here
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database.sqlite

QUEUE_CONNECTION=database

# Add other configurations as needed
```

### Docker Environment

When running with Docker, ensure your `.env` file is properly configured for the containerized environment:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/var/www/database/database.sqlite
```

## Queue System

This application uses Laravel's queue system for background processing.

### With Docker

Queue workers are automatically started using Supervisor when the container runs.

### Without Docker

Start the queue worker manually:

```bash
# Basic queue worker
php artisan queue:work

# With specific configuration
php artisan queue:work --tries=3 --timeout=60 --sleep=3

# For development (restarts automatically on code changes)
php artisan queue:listen
```

### Queue Management

```bash
# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear all jobs
php artisan queue:clear
```

## Testing

Run the application tests:

```bash
# Run all tests
php artisan test

# Run with coverage (if configured)
php artisan test --coverage

# Run specific test suite
php artisan test --testsuite=Feature
```

### API Testing

Use the provided Postman collection to test all API endpoints:

1. Import `MogRex.postman_collection.json` into Postman
2. Set up environment variables (base URL, auth tokens)
3. Run individual requests or the entire collection

## Deployment

### Docker Deployment

The application is containerized and ready for deployment on platforms like:

- Render
- DigitalOcean App Platform
- AWS ECS
- Google Cloud Run

### Key Docker Features

- Multi-stage build for optimization
- Supervisor for queue worker management
- Proper file permissions and security
- Health checks and logging

### Environment Variables for Production

Ensure these are set in your production environment:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
DB_CONNECTION=mysql # or your preferred database
QUEUE_CONNECTION=redis # recommended for production
```

## Development Notes

- Built with Laravel 10.x
- Uses PHP 8.2+ features
- Follows PSR standards and Laravel best practices
- Implements proper error handling and validation
- Queue-based architecture for scalability

## Project Structure

```
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   ├── Jobs/
│   └── ...
├── database/
│   ├── migrations/
│   └── seeders/
├── tests/
├── docker/
├── Dockerfile
├── MogRex.postman_collection.json
└── README.md
```

## Support

For questions or issues related to this assessment, please contact the development team or refer to the Laravel documentation.

---

**Note**: This application was developed as part of the technical assessment for the Software Engineer position at Morgex. It demonstrates proficiency in Laravel development, Docker containerization, API design, and modern PHP practices.
