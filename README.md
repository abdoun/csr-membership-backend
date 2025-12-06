# CSR Membership Backend

A Symfony-based membership management system API.

## 🚀 Tech Stack

-   **Framework**: Symfony 8.0
-   **Language**: PHP 8.4
-   **Database**: MySQL 8.0
-   **Containerization**: Docker & Docker Compose

## 🛠 Setup & Installation

This project uses a `Makefile` to simplify Docker management.

### Prerequisites

-   Docker
-   Docker Compose
-   Make (optional, but recommended)

### Quick Start

1.  **Build and Start the Application**:
    ```bash
    make build
    make db   # Starts the database container
    make run  # Starts the app container
    ```
    *Alternatively, using docker commands directly:*
    ```bash
    docker build -t csr-membership-app .
    docker network create csr-network
    docker run -d --name csr-membership-db --network csr-network -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=csr_membership -e MYSQL_USER=user -e MYSQL_PASSWORD=password mysql:8.0
    docker run -d --name csr-membership-app --network csr-network -p 8000:80 -v $(pwd):/var/www/html csr-membership-app
    ```

2.  **Run Migrations**:
    The application needs the database schema to be set up.
    ```bash
    docker exec csr-membership-app php bin/console doctrine:migrations:migrate
    ```
    This will also seed the default admin user.

## 🔑 Authentication & Access Control

This API uses a simulated authentication mechanism for demonstration purposes.
**Header**: `X-Requester-Id: <user_id>`

### User Roles (`UserLevel`)

-   **Admin** (`admin`): Full access (Create, Read, Update, Delete).
-   **Advanced** (`advanced`): Can only update their own password.
-   **Basic** (`basic`): Can only update their own password.

### Default Credentials

A default admin user is created by the migrations:

-   **Username**: `admin`
-   **Password**: `admin`
-   **ID**: `2` (Use `X-Requester-Id: 2` to act as this admin)

## 📡 API Endpoints

Base URL: `http://localhost:8000/api/users`

| Method | Endpoint | Description | Access |
| :--- | :--- | :--- | :--- |
| `GET` | `/api/users` | List all users | **Admin** only |
| `GET` | `/api/users/{id}` | Get user details | **Admin** only |
| `POST` | `/api/users` | Create a new user | **Admin** only |
| `PUT` | `/api/users/{id}` | Update a user | **Admin** (all fields) / **Owner** (password only) |
| `DELETE` | `/api/users/{id}` | Delete a user | **Admin** only |

### Example: Create User (Admin)

```bash
curl -X POST http://localhost:8000/api/users \
  -H "X-Requester-Id: 2" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Doe",
    "username": "janedoe",
    "password": "password123",
    "level": "basic",
    "active": true
  }'
```

## 📂 Project Structure

-   `src/Controller`: API Controllers
-   `src/Entity`: Database Entities
-   `src/Enum`: PHP Enums (UserLevel)
-   `migrations`: Database migrations
-   `docker-compose.yml`: Docker services configuration
-   `Dockerfile`: PHP application image definition
