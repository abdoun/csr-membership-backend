# Testing Guide

This document provides comprehensive information about testing the CSR Membership Backend API.

## Overview

The project includes two types of tests:

1. **Unit Tests** - Test individual classes and methods in isolation
2. **Application Tests** - Test complete authentication and API workflows

### Test Statistics

- **Total Tests**: 22
- **Unit Tests**: 6
- **Application Tests**: 16 (11 Authentication, 5 User Management)
- **Code Coverage**: Core authentication and API endpoints

## Running Tests

### Quick Start

```bash
# Run all tests
make test

# Run with detailed output
sudo docker exec csr-membership-app php bin/phpunit --verbose

# Run specific test file
sudo docker exec csr-membership-app php bin/phpunit tests/Unit/UserTest.php
```

### Initialize Test Environment

The test environment is automatically set up with `make init`:

```bash
make init
```

This command:
1. Builds the Docker image
2. Creates the database network and container
3. Starts the application container
4. Sets up the test database
5. Runs migrations on both dev and test databases

### Manual Test Setup

If you need to manually set up the test database:

```bash
# Create test database
sudo docker exec -e APP_ENV=test csr-membership-app php bin/console doctrine:database:create

# Run migrations on test database
sudo docker exec -e APP_ENV=test csr-membership-app php bin/console doctrine:migrations:migrate --no-interaction

# Run tests
sudo docker exec csr-membership-app php bin/phpunit
```

## Test Structure

### Unit Tests (`tests/Unit/`)

#### UserTest.php
Tests the User entity and its role-based access control:

- `testUserEntity()` - Basic user properties (name, username, password, level, active)
- `testAdminUserRoles()` - Admin users have ROLE_ADMIN + ROLE_USER
- `testAdvancedUserRoles()` - Advanced users have ROLE_ADVANCED + ROLE_USER
- `testBasicUserRoles()` - Basic users have ROLE_USER
- `testUserIdentifier()` - Tests getUserIdentifier() returns username
- `testEraseCredentials()` - Tests eraseCredentials() doesn't throw exceptions

### Application Tests (`tests/Application/`)

#### AuthControllerTest.php
Tests JWT authentication system:

**Login Tests:**
- `testLoginWithValidCredentials()` - Login with correct admin/admin credentials
  - ✅ Returns JWT token
  - ✅ Returns user data with username and roles
  
- `testLoginWithInvalidPassword()` - Login with wrong password
  - ✅ Returns 401 Unauthorized
  
- `testLoginWithNonexistentUser()` - Login with non-existent username
  - ✅ Returns 401 Unauthorized
  
- `testLoginWithMissingUsername()` - POST without username field
  - ✅ Returns 401 Unauthorized
  
- `testLoginWithMissingPassword()` - POST without password field
  - ✅ Returns 401 Unauthorized

**Token Validation Tests:**
- `testJwtTokenIsValidForProtectedEndpoints()` - Token grants access to protected endpoints
  - ✅ Returns 200 OK with user list
  
- `testAccessProtectedEndpointWithoutToken()` - No Authorization header
  - ✅ Returns 401 Not Privileged
  
- `testAccessProtectedEndpointWithInvalidToken()` - Invalid JWT format
  - ✅ Returns 401 Unauthorized

**Logout Tests:**
- `testLogoutWithValidToken()` - Logout with valid JWT
  - ✅ Returns 200 OK with logout message
  
- `testLogoutWithoutToken()` - Logout without authentication
  - ✅ Returns 401 Unauthorized

**JWT Claims Tests:**
- `testTokenContainsCorrectClaims()` - JWT contains required claims
  - ✅ Has `iat` (issued at) claim
  - ✅ Has `exp` (expiration) claim
  - ✅ Has `sub` (subject/username) claim
  - ✅ Has `roles` claim with correct role

#### UserControllerTest.php
Tests User API endpoints:

**Read Tests:**
- `testGetUsersAsAdmin()` - Admin can list all users
  - ✅ Returns 200 OK with user list
  
- `testGetUsersAsBasicUser()` - Basic user cannot access admin endpoint
  - ✅ Returns 403 Forbidden

**Create Tests:**
- `testCreateUserAsAdmin()` - Admin can create new users
  - ✅ Returns 201 Created
  - ✅ User exists in database

**Update Tests:**
- `testUpdateUser()` - Tests three scenarios:
  1. Admin updates any user ✅
  2. User updates themselves ✅
  3. User tries to update another user (forbidden) ✅

**Delete Tests:**
- `testDeleteUser()` - Tests two scenarios:
  1. Non-admin tries to delete (forbidden) ✅
  2. Admin deletes user ✅

## Test Database Configuration

The test environment uses a separate MySQL database: `csr_membership_test`

**Credentials:**
- Host: csr-membership-db
- User: user
- Password: password
- Database: csr_membership_test

**Key Differences from Dev:**
- Uses `APP_ENV=test` Symfony environment
- Runs migrations to create tables
- Includes seed data (admin user with password "admin")
- Isolated from development data

## Test Data

The migrations automatically seed the test database with:

```
Admin User
├─ username: admin
├─ password: admin (bcrypt: $2y$12$z.T5wBhT5.fDa65N3QFqxeiqfqKSF8e1whq7KsKsxR4njl1FMEhIW)
├─ level: admin
├─ name: Admin User
└─ active: true
```

Additional test users are created dynamically during tests with unique usernames (using `uniqid()`) to avoid conflicts.

## Authentication Testing Strategy

Tests use **JWT Bearer tokens** for authentication:

1. **Get Token**: Call POST `/api/auth/login` with credentials
2. **Extract Token**: Parse JSON response for `token` field
3. **Use Token**: Add `Authorization: Bearer <token>` header to requests
4. **Verify Access**: Assert expected response status codes

### Example Test Flow

```php
// 1. Login to get token
$this->client->request(
    'POST',
    '/api/auth/login',
    [],
    [],
    ['CONTENT_TYPE' => 'application/json'],
    json_encode(['username' => 'admin', 'password' => 'admin'])
);
$response = json_decode($this->client->getResponse()->getContent(), true);
$token = $response['token'];

// 2. Use token to access protected endpoint
$this->client->request(
    'GET',
    '/api/users',
    [],
    [],
    ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
);

// 3. Assert access granted
$this->assertResponseIsSuccessful();
```

## Role-Based Access Control Testing

Tests verify that role-based access control works correctly:

| Endpoint | ROLE_ADMIN | ROLE_ADVANCED | ROLE_USER |
|----------|-----------|---------------|-----------|
| GET /api/users | ✅ 200 | ❌ 403 | ❌ 403 |
| POST /api/users | ✅ 201 | ❌ 403 | ❌ 403 |
| PUT /api/users/{id} | ✅ 200 (any) | ✅ 200 (self) | ✅ 200 (self) |
| DELETE /api/users/{id} | ✅ 204 | ❌ 403 | ❌ 403 |

## CI/CD Integration

The test suite is designed for CI/CD pipelines:

```bash
# In your CI pipeline (e.g., GitHub Actions, GitLab CI)
make init
make test

# Exit code 0 = all tests pass
# Exit code 1 = tests failed
```

## Debugging Tests

### Run with Verbose Output

```bash
sudo docker exec csr-membership-app php bin/phpunit --verbose
```

### Run Specific Test

```bash
# Run single test file
sudo docker exec csr-membership-app php bin/phpunit tests/Application/AuthControllerTest.php

# Run single test method
sudo docker exec csr-membership-app php bin/phpunit --filter testLoginWithValidCredentials
```

### View Test Coverage

```bash
# Generate HTML coverage report
sudo docker exec csr-membership-app php bin/phpunit --coverage-html=coverage

# View in browser
open coverage/index.html
```

### Check Test Database State

```bash
# Connect to test database
sudo docker exec -it csr-membership-db mysql -uuser -ppassword csr_membership_test

# View users
mysql> SELECT id, username, level FROM users;
```

## Common Issues and Solutions

### Issue: "Access denied for user 'user'@'%' to database 'csr_membership_test'"

**Solution:**
```bash
# Grant permissions again
sudo docker exec csr-membership-db mysql -uroot -proot -e \
  "GRANT ALL PRIVILEGES ON csr_membership_test.* TO 'user'@'%'; FLUSH PRIVILEGES;"

# Recreate test database
sudo docker exec -e APP_ENV=test csr-membership-app php bin/console doctrine:database:drop --if-exists --force
sudo docker exec -e APP_ENV=test csr-membership-app php bin/console doctrine:database:create
sudo docker exec -e APP_ENV=test csr-membership-app php bin/console doctrine:migrations:migrate --no-interaction
```

### Issue: "Admin user not found"

**Solution:**
```bash
# Verify migrations ran
sudo docker exec -e APP_ENV=test csr-membership-app php bin/console doctrine:migrations:status

# Check database content
sudo docker exec -it csr-membership-db mysql -uuser -ppassword csr_membership_test -e "SELECT * FROM users;"
```

### Issue: "ServiceNotFoundException: security.password_hasher"

**Solution:** Tests use direct bcrypt hashes instead of password hasher service. This is by design to avoid dependency injection issues in test environment.

## Best Practices

1. **Use Unique Usernames**: Tests create users with `uniqid()` to avoid conflicts
2. **Explicit Passwords**: Use bcrypt hashes directly: `$2y$12$z.T5wBhT5.fDa65N3QFqxeiqfqKSF8e1whq7KsKsxR4njl1FMEhIW`
3. **Clear Entity Manager**: Call `$this->entityManager->clear()` between database operations
4. **Get Fresh Data**: After updates, fetch entity again from database to verify changes
5. **Test Isolation**: Each test creates its own test data and doesn't depend on others

## Test Categories by Feature

### Authentication (11 tests)
- Login with valid/invalid credentials ✅
- Missing username/password handling ✅
- JWT token validation ✅
- Protected endpoints access control ✅
- Logout functionality ✅
- JWT claims verification ✅

### User Management (5 tests)
- List users (admin only) ✅
- Create users (admin only) ✅
- Update users (admin or self) ✅
- Delete users (admin only) ✅
- Role-based authorization ✅

### User Model (6 tests)
- Entity properties ✅
- Role mapping by user level ✅
- User identifier ✅
- Credential erasure ✅

## Performance

- **Total Runtime**: ~3 seconds
- **Tests per Second**: ~7.3
- **Memory Usage**: ~36 MB

## Next Steps

To add more tests:

1. Create test file in `tests/Application/` or `tests/Unit/`
2. Extend `WebTestCase` for API tests or `TestCase` for unit tests
3. Use existing tests as templates
4. Run `make test` to verify
5. Update this documentation with new test descriptions

## Resources

- [PHPUnit Documentation](https://phpunit.readthedocs.io/)
- [Symfony Testing](https://symfony.com/doc/current/testing.html)
- [JWT Authentication](https://datatracker.ietf.org/doc/html/rfc7519)
- [API Testing Best Practices](https://restfulapi.net/http-status-codes/)
