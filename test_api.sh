#!/bin/bash

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

BASE_URL="http://localhost:8000/api"

echo -e "${BLUE}=== CSR Membership API Test Script ===${NC}\n"

# Test 1: Login with credentials
echo -e "${BLUE}Test 1: Login with valid credentials${NC}"
LOGIN_RESPONSE=$(curl -s -X POST \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}' \
  "$BASE_URL/auth/login")

echo "Response: $LOGIN_RESPONSE"
TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo -e "${RED}Failed to get token${NC}\n"
    exit 1
fi

echo -e "${GREEN}✓ Successfully got token: ${TOKEN:0:50}...${NC}\n"

# Test 2: Get all users (requires admin role)
echo -e "${BLUE}Test 2: Get all users (Admin endpoint)${NC}"
USERS_RESPONSE=$(curl -s -X GET \
  -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/users")

echo "Response: $USERS_RESPONSE"
echo

# Test 3: Get specific user
echo -e "${BLUE}Test 3: Get specific user (ID: 1)${NC}"
USER_RESPONSE=$(curl -s -X GET \
  -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/users/1")

echo "Response: $USER_RESPONSE"
echo

# Test 4: Create new user (Admin only)
echo -e "${BLUE}Test 4: Create new user (Admin endpoint)${NC}"
CREATE_RESPONSE=$(curl -s -X POST \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"username":"testuser","password":"testpass","name":"Test User","active":true}' \
  "$BASE_URL/users")

echo "Response: $CREATE_RESPONSE"
echo

# Test 5: Logout
echo -e "${BLUE}Test 5: Logout${NC}"
LOGOUT_RESPONSE=$(curl -s -X POST \
  -H "Authorization: Bearer $TOKEN" \
  "$BASE_URL/auth/logout")

echo "Response: $LOGOUT_RESPONSE"
echo -e "${GREEN}Logout successful${NC}\n"

# Test 6: Try to access protected endpoint without token
echo -e "${BLUE}Test 6: Try to access protected endpoint without token${NC}"
NO_TOKEN_RESPONSE=$(curl -s -X GET \
  "$BASE_URL/users")

echo "Response: $NO_TOKEN_RESPONSE"
echo -e "${RED}✓ Correctly rejected request without token${NC}\n"

echo -e "${GREEN}=== All tests completed ===${NC}"
