# Quick Reference - JWT Authentication API

## Login
```
POST /api/auth/login
Content-Type: application/json

{
  "username": "admin",
  "password": "password"
}

Response 200 OK:
{
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "user": {
    "id": 1,
    "username": "admin",
    "name": "Administrator",
    "level": "admin",
    "roles": ["ROLE_ADMIN"]
  }
}
```

## Using Token
Every authenticated request needs this header:
```
Authorization: Bearer <your-token-here>
```

## Get All Users (Admin Only)
```
GET /api/users
Authorization: Bearer <token>

Response 200 OK:
[
  {
    "id": 1,
    "username": "admin",
    "name": "Administrator",
    "level": "admin",
    "active": true
  }
]
```

## Get Single User (Admin Only)
```
GET /api/users/1
Authorization: Bearer <token>

Response 200 OK:
{
  "id": 1,
  "username": "admin",
  "name": "Administrator",
  "level": "admin",
  "active": true
}
```

## Create User (Admin Only)
```
POST /api/users
Authorization: Bearer <token>
Content-Type: application/json

{
  "username": "newuser",
  "password": "securepassword",
  "name": "New User",
  "level": "basic",
  "active": true
}

Response 201 Created:
{
  "id": 2,
  "username": "newuser",
  "name": "New User",
  "level": "basic",
  "active": true
}
```

## Update User
```
PUT /api/users/2
Authorization: Bearer <token>
Content-Type: application/json

{
  "name": "Updated Name",
  "password": "newpassword"
}

Response 200 OK:
{
  "id": 2,
  "username": "newuser",
  "name": "Updated Name",
  "level": "basic",
  "active": true
}

Note: 
- Admin can update anything
- Non-admin can only update themselves
- Can always update password (if allowed)
```

## Delete User (Admin Only)
```
DELETE /api/users/2
Authorization: Bearer <token>

Response 204 No Content
```

## Logout
```
POST /api/auth/logout
Authorization: Bearer <token>

Response 200 OK:
{
  "message": "Successfully logged out"
}

Note: Client-side action - remove token from storage
```

## Error Responses

### 401 Unauthorized (No/Invalid Token)
```json
{
  "error": "Authentication required",
  "message": "Missing or invalid JWT token"
}
```

### 403 Forbidden (Insufficient Permissions)
```json
{
  "error": "Access denied"
}
```

### 404 Not Found
```json
{
  "error": "User not found"
}
```

### 400 Bad Request
```json
{
  "error": "Invalid level"
}
```

## Role Requirements

| Endpoint | Method | Role | Self Access |
|----------|--------|------|-------------|
| /api/auth/login | POST | - | ✓ |
| /api/auth/logout | POST | ROLE_USER | ✓ |
| /api/users | GET | ROLE_ADMIN | ✗ |
| /api/users | POST | ROLE_ADMIN | ✗ |
| /api/users/{id} | GET | ROLE_ADMIN | ✗ |
| /api/users/{id} | PUT | ROLE_USER | ✓ if self, admin any |
| /api/users/{id} | DELETE | ROLE_ADMIN | ✗ |

## User Levels & Roles

| Level | Role(s) |
|-------|---------|
| admin | ROLE_ADMIN |
| advanced | ROLE_ADVANCED |
| basic | ROLE_USER |

## Common cURL Examples

### Login and Save Token
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}' \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

echo "Token: $TOKEN"
```

### List Users with Token
```bash
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer $TOKEN"
```

### Create User
```bash
curl -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "username":"newuser",
    "password":"pass123",
    "name":"New User",
    "level":"basic",
    "active":true
  }'
```

### Update Own Profile
```bash
curl -X PUT http://localhost:8000/api/users/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"My New Name"}'
```

## Headers Required

```
Authorization: Bearer <token>
Content-Type: application/json
```

## Environment Variables

Set in `.env`:
```
JWT_SECRET_KEY=your-secret-key
JWT_TOKEN_TTL=3600
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

## Token Claims

JWT tokens contain:
```json
{
  "iat": 1234567890,      // Issued at (timestamp)
  "exp": 1234571490,      // Expires at (timestamp)
  "sub": "admin",         // Subject (username)
  "roles": ["ROLE_ADMIN"] // User roles
}
```

Decode token at: https://jwt.io

---

**Server**: http://localhost:8000  
**API Base**: http://localhost:8000/api  
**Token TTL**: 3600 seconds (1 hour)
