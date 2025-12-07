# JWT Authentication, CORS & Authorization Implementation

## Overview
This backend now implements:
- **JWT (JSON Web Token)** authentication for stateless API security
- **CORS (Cross-Origin Resource Sharing)** configuration for frontend integration
- **Login/Logout** endpoints with token generation
- **Role-based Access Control (RBAC)** with three user levels: ADMIN, ADVANCED, BASIC

## Architecture

### Authentication Flow
1. User sends credentials to `/api/auth/login`
2. Server validates credentials and generates JWT token
3. User includes token in `Authorization: Bearer <token>` header for subsequent requests
4. Server validates JWT token for each protected request
5. User can call `/api/auth/logout` to clear the token on client-side (stateless, no server-side action)

### Security Components

#### 1. **User Entity** (`src/Entity/User.php`)
- Implements `UserInterface` and `PasswordAuthenticatedUserInterface`
- Provides roles based on `UserLevel` enum:
  - `ADMIN` → `ROLE_ADMIN`
  - `ADVANCED` → `ROLE_ADVANCED`
  - `BASIC` → `ROLE_USER`

#### 2. **Authenticators** (`src/Security/`)

**JsonLoginAuthenticator.php**
- Handles login requests at `/api/auth/login`
- Validates username/password credentials
- Generates JWT token on successful authentication
- Returns token + user info to client

**JwtAuthenticator.php**
- Validates JWT tokens in `Authorization: Bearer <token>` header
- Extracts user information from token claims
- Used for all protected endpoints

**JwtAuthenticationEntryPoint.php**
- Handles authentication failures for JWT endpoints
- Returns 401 Unauthorized with error message

#### 3. **Configuration Files**

**config/packages/security.yaml**
```yaml
security:
    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: username
    
    firewalls:
        api_login:    # Allow login without authentication
            pattern: ^/api/auth/login
            custom_authenticator: App\Security\JsonLoginAuthenticator
        
        api:          # Protect all other API endpoints
            pattern: ^/api/
            custom_authenticator: App\Security\JwtAuthenticator
            entry_point: App\Security\JwtAuthenticationEntryPoint
    
    access_control:
        - { path: ^/api/auth/login, roles: PUBLIC_ACCESS }
        - { path: ^/api/, roles: ROLE_USER }
```

**config/packages/nelmio_cors.yaml**
```yaml
nelmio_cors:
    defaults:
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
        allow_headers: ['Content-Type', 'Authorization']
        allow_credentials: true
```

### Environment Variables

Add to `.env` file:
```env
JWT_SECRET_KEY=your-secret-key-change-in-production
JWT_TOKEN_TTL=3600  # Token valid for 1 hour
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

## API Endpoints

### Authentication

**POST /api/auth/login**
```json
{
  "username": "admin",
  "password": "password"
}
```

Response (200 OK):
```json
{
  "token": "eyJhbGc...",
  "user": {
    "id": 1,
    "username": "admin",
    "name": "Administrator",
    "level": "admin",
    "roles": ["ROLE_ADMIN"]
  }
}
```

**POST /api/auth/logout**
- Requires: `Authorization: Bearer <token>`
- Response (200 OK):
```json
{
  "message": "Successfully logged out"
}
```

### Users (Protected Endpoints)

**GET /api/users** - ADMIN ONLY
- List all users
- Requires: `Authorization: Bearer <token>`
- Requires: `ROLE_ADMIN`

**GET /api/users/{id}** - ADMIN ONLY
- Get specific user
- Requires: `ROLE_ADMIN`

**POST /api/users** - ADMIN ONLY
- Create new user
- Requires: `ROLE_ADMIN`
- Body:
```json
{
  "username": "newuser",
  "password": "securepass",
  "name": "New User",
  "level": "basic",
  "active": true
}
```

**PUT /api/users/{id}** - USER LEVEL
- Update user (self or admin)
- ADMIN: Can update anything
- BASIC/ADVANCED: Can only update themselves
- Can update password, name, username (non-admin limited)

**DELETE /api/users/{id}** - ADMIN ONLY
- Delete user
- Requires: `ROLE_ADMIN`

## Testing

### Using cURL

**1. Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

**2. Use token in subsequent requests:**
```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer eyJhbGc..."
```

**3. Logout (client-side: remove token):**
```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer eyJhbGc..."
```

### Using the Test Script
```bash
chmod +x test_api.sh
./test_api.sh
```

## Password Hashing

Passwords are automatically hashed using Symfony's `PasswordHasher` with:
- Algorithm: Auto (uses bcrypt by default)
- Cost: Default (depends on environment)

## CORS Headers

The API now includes CORS headers allowing:
- Credentials in cross-origin requests
- All standard HTTP methods
- `Authorization` header for token passing
- Requests from `localhost` on any port (configurable via `CORS_ALLOW_ORIGIN`)

## Security Best Practices

1. **Change JWT_SECRET_KEY** in production
2. **Use HTTPS** in production
3. **Short token TTL** (default: 1 hour)
4. **Validate** all incoming data
5. **Log** authentication attempts (recommended)
6. **Rate limit** login endpoint
7. **Use environment variables** for secrets
8. **Regular token rotation** (re-login periodically)

## Troubleshooting

**Token not recognized:**
- Check `Authorization` header format: `Bearer <token>`
- Verify token hasn't expired (check TTL)
- Ensure JWT_SECRET_KEY matches across requests

**CORS errors:**
- Check `CORS_ALLOW_ORIGIN` in `.env`
- Verify request origin matches pattern
- Ensure `Authorization` is in `allow_headers`

**Access denied despite valid token:**
- Check user's `level` and required role
- Verify user `active` status
- Ensure user exists in database

## Dependencies

- `firebase/php-jwt` - JWT token generation and validation
- `symfony/security-bundle` - Security framework
- `symfony/password-hasher` - Password hashing
- `nelmio/cors-bundle` - CORS support
