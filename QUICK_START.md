# Quick Reference Card - JWT Authentication

## 🔐 Login Flow

```bash
# 1. Send login credentials
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'

# 2. Receive JWT token
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3NjU...",
  "user": {
    "id": 1,
    "username": "admin",
    "name": "Admin User",
    "level": "admin",
    "roles": ["ROLE_ADMIN", "ROLE_USER"]
  }
}

# 3. Use token on protected endpoints
TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer $TOKEN"

# 4. Logout
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer $TOKEN"
```

---

## 📋 Available Endpoints

| Method | Endpoint | Role | Purpose |
|--------|----------|------|---------|
| POST | `/api/auth/login` | PUBLIC | Get JWT token |
| POST | `/api/auth/logout` | ROLE_USER | Logout |
| GET | `/api/users` | ROLE_ADMIN | List users |
| GET | `/api/users/{id}` | ROLE_ADMIN | Get user |
| POST | `/api/users` | ROLE_ADMIN | Create user |
| PUT | `/api/users/{id}` | ROLE_ADMIN / Self | Update user |
| DELETE | `/api/users/{id}` | ROLE_ADMIN | Delete user |

---

## 🛠 Make Commands

```bash
make init          # Initialize everything
make test          # Run test suite
make migrate       # Run migrations
make stop          # Stop containers
make logs          # View logs
make clean         # Clean up everything
```

---

## 💾 Test Credentials

**Admin User**
```
Username: admin
Password: admin
Roles: ROLE_ADMIN, ROLE_USER
```

---

## 🔑 JWT Token Structure

```
header.payload.signature

Payload contains:
{
  "iat": 1765115748,              // Issued at
  "exp": 1765119348,              // Expires at (1 hour)
  "sub": "admin",                 // Username
  "roles": ["ROLE_ADMIN"]         // User roles
}
```

---

## ✅ Test Coverage

- **AuthControllerTest** (13 tests)
  - Login validation
  - Token generation
  - Protected endpoint access
  - Logout functionality

- **UserControllerTest** (6 tests)
  - User CRUD operations
  - Role-based access control
  - Permission enforcement

- **UserTest** (3 tests)
  - Entity properties
  - Role mappings
  - User identifier

**Result**: 22/22 passing ✅

---

## 🔒 Security Headers

When making requests with token:

```bash
Authorization: Bearer <token>
```

---

## 📝 Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 204 | No Content |
| 400 | Bad Request |
| 401 | Unauthorized (no/invalid token) |
| 403 | Forbidden (insufficient permissions) |
| 404 | Not Found |
| 500 | Server Error |

---

## 🚀 Frontend Integration

```javascript
// 1. Login
const response = await fetch('http://localhost:8000/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ username: 'admin', password: 'admin' })
});

const { token } = await response.json();
localStorage.setItem('token', token);

// 2. Protected Requests
const result = await fetch('http://localhost:8000/api/users', {
  headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
});

// 3. Logout
await fetch('http://localhost:8000/api/auth/logout', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
});

localStorage.removeItem('token');
```

---

## 🎯 Troubleshooting

**401 Unauthorized**
- Token missing or invalid
- Token expired (after 1 hour)
- Wrong credentials during login

**403 Forbidden**
- User doesn't have required role
- Trying to update someone else's data (non-admin)

**500 Server Error**
- Check logs: `make logs`
- Verify database connection
- Check JWT_SECRET_KEY configuration

---

## 📚 Full Documentation

See these files for detailed information:
- `IMPLEMENTATION_COMPLETE.md` - Overview
- `JWT_AUTH_GUIDE.md` - Deep dive
- `TESTING.md` - Test guide
- `QUICK_REFERENCE.md` - Commands
- `FEATURES.md` - Features list

---

**Last Updated**: December 7, 2025  
**Status**: Production Ready ✅
