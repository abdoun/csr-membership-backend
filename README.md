# CSR Membership Backend

A Symfony-based membership management system API with JWT authentication, CORS support, and role-based access control.

## 🚀 Tech Stack

-   **Framework**: Symfony 8.0
-   **Language**: PHP 8.4
-   **Database**: MySQL 8.0
-   **Containerization**: Docker & Docker Compose
-   **Authentication**: JWT (JSON Web Tokens) with Firebase JWT
-   **CORS**: nelmio/cors-bundle for cross-origin requests
-   **Testing**: PHPUnit 12.5.1

## 🛠 Setup & Installation

This project uses a `Makefile` to simplify Docker management.

### Prerequisites

-   Docker
-   Docker Compose
-   Make (recommended)

### Quick Start

1.  **Initialize Everything** (Recommended):
    ```bash
    make init
    # This runs: build → network → db → run → setup-test-db → migrate
    ```

2.  **Or Step by Step**:
    ```bash
    make build          # Build Docker image
    make db             # Start MySQL container
    make run            # Start app container
    make migrate        # Run migrations
    ```

3.  **Run Tests** (Optional):
    ```bash
    make test           # Run 22-test suite (should all pass)
    ```

## 🔐 Authentication

This API uses **JWT (JSON Web Tokens)** for stateless authentication with CORS support for cross-origin requests.

### How JWT Authentication Works

1. **Login**: POST `/api/auth/login` with username/password → Get JWT token
2. **Authenticate**: Include token in Authorization header: `Authorization: Bearer <token>`
3. **Protected Endpoints**: All endpoints require valid JWT token except login
4. **Logout**: POST `/api/auth/logout` to invalidate session

### Login Example

```bash
# 1. Login with credentials
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "username": "admin",
    "password": "admin"
  }'

# Response:
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 1,
    "username": "admin",
    "name": "Admin User",
    "level": "admin",
    "roles": ["ROLE_ADMIN", "ROLE_USER"]
  }
}

# 2. Use token on protected endpoints
TOKEN="<token-from-response>"
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer $TOKEN"
```

### User Roles & Permissions

Three user levels with corresponding roles:

| Level | Roles | GET /users | POST /users | PUT /users/{id} | DELETE /users/{id} |
|-------|-------|-----------|-----------|-----------------|-------------------|
| **Admin** | ROLE_ADMIN, ROLE_USER | ✅ | ✅ | ✅ (any) | ✅ |
| **Advanced** | ROLE_ADVANCED, ROLE_USER | ❌ | ❌ | ✅ (self) | ❌ |
| **Basic** | ROLE_USER | ❌ | ❌ | ✅ (self) | ❌ |

### Default Test Credentials

```
Username: admin
Password: admin
Role: ROLE_ADMIN
```

### Token Details

- **Algorithm**: HS256
- **TTL**: 1 hour (configurable via JWT_TOKEN_TTL env var)
- **Claims**: iat (issued at), exp (expiration), sub (username), roles

## 📡 API Endpoints

Base URL: `http://localhost:8000/api`

### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---|
| `POST` | `/auth/login` | Login with credentials, get JWT token | ❌ No |
| `POST` | `/auth/logout` | Logout (invalidate session) | ✅ Yes |

### User Management Endpoints

| Method | Endpoint | Description | Required Role |
|--------|----------|-------------|---|
| `GET` | `/users` | List all users | ROLE_ADMIN |
| `GET` | `/users/{id}` | Get user details | ROLE_ADMIN |
| `POST` | `/users` | Create new user | ROLE_ADMIN |
| `PUT` | `/users/{id}` | Update user | ROLE_ADMIN or owner (self) |
| `DELETE` | `/users/{id}` | Delete user | ROLE_ADMIN |

### Example Requests

**Login and Get Token**:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'
```

**Get Users (with token)**:
```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer <token>"
```

**Create User (Admin only)**:
```bash
curl -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "username": "janedoe",
    "password": "password123",
    "level": "basic",
    "active": true
  }'
```

**Update User (Admin or self)**:
```bash
curl -X PUT http://localhost:8000/api/users/2 \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{"name": "Updated Name"}'
```

**Logout**:
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer <token>"
```

## 🧪 Testing

The project includes a comprehensive test suite with 22 tests covering authentication, authorization, and user management.

### Run Tests

```bash
make test
# Output: OK (22 tests, 59 assertions)
```

### Test Coverage

- **Authentication** (13 tests): Login, logout, token validation, protected endpoints
- **User Management** (6 tests): CRUD operations, role-based access control
- **Entity Validation** (3 tests): User properties, role mappings

### Test Database

Tests use a separate MySQL database (`csr_membership_test`) with isolated migrations. Test data is automatically created and cleaned up between runs.

## 📂 Project Structure

```
src/
├── Controller/
│   ├── AuthController.php          # Login/logout endpoints
│   └── UserController.php          # User CRUD endpoints
├── Security/
│   ├── JwtAuthenticator.php        # JWT token validation
│   ├── JsonLoginAuthenticator.php  # Login handler & token generation
│   └── JwtAuthenticationEntryPoint.php
└── Entity/
    └── User.php                    # User entity with JWT roles

config/
├── packages/
│   ├── security.yaml              # Authentication & authorization config
│   └── nelmio_cors.yaml           # CORS configuration
└── routes/
    └── security.yaml              # Auth routes

tests/
├── Application/
│   ├── AuthControllerTest.php      # JWT authentication tests
│   └── UserControllerTest.php      # User endpoint tests
└── Unit/
    └── UserTest.php               # User entity unit tests

migrations/
└── Version*.php                    # Database migrations & seed data

Dockerfile                          # Application container definition
Makefile                            # Build & deployment automation
docker-compose.yml                  # Services configuration
```

## 🔧 Makefile Commands

```bash
make init           # Complete setup (build, db, run, migrations, tests)
make build          # Build Docker image
make db             # Start MySQL container
make run            # Start application container
make migrate        # Run database migrations (dev)
make migrate-test   # Run database migrations (test)
make test           # Run test suite
make stop           # Stop containers
make clean          # Remove all containers and networks
make logs           # View application logs
```

## 🔐 Security Features

- **JWT Authentication**: Stateless, token-based authentication
- **Bcrypt Password Hashing**: Industry-standard password security (cost 12)
- **Role-Based Access Control**: 3-level permission system
- **CORS Support**: Configurable cross-origin request handling
- **Proper HTTP Status Codes**: 401 (Unauthorized), 403 (Forbidden)
- **Token Expiration**: Configurable TTL (default 1 hour)

## 📚 Documentation

Additional documentation files are available:

- **IMPLEMENTATION_COMPLETE.md** - Full implementation overview and status
- **QUICK_START.md** - Quick reference guide for getting started
- **JWT_AUTH_GUIDE.md** - Detailed JWT authentication guide with examples
- **TESTING.md** - Testing infrastructure and test suite usage
- **QUICK_REFERENCE.md** - Command and endpoint reference
- **MAKEFILE.md** - Detailed Makefile documentation and targets
- **FEATURES.md** - Feature list and descriptions
- **COMPLETE_GUIDE.md** - Full walkthrough and integration guide
- **CHECKLIST.md** - Implementation checklist and verification steps
- **IMPLEMENTATION_SUMMARY.md** - Summary of implementation details

## 🚀 Deployment

### Environment Variables

Create `.env` file with:

```bash
JWT_SECRET_KEY=<your-secret-key>
JWT_TOKEN_TTL=3600
CORS_ALLOW_ORIGIN="*"  # Change for production
```

### Production Checklist

- [ ] Generate secure JWT_SECRET_KEY
- [ ] Configure CORS_ALLOW_ORIGIN for your domain
- [ ] Set JWT_TOKEN_TTL based on requirements
- [ ] Use environment-specific Docker configuration
- [ ] Run migrations on production database
- [ ] Verify all tests pass

## 📞 Support & Troubleshooting

**Tests failing?**
```bash
make clean && make init && make test
```

**Need to reset database?**
```bash
make clean
make init
```

**View application logs?**
```bash
make logs
```

**Test a specific endpoint?**
```bash
# Get token first
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}' | jq -r '.token')

# Use token
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer $TOKEN"
```
