# Makefile Commands Reference

This project includes convenient Makefile targets for development, deployment, and testing.

## Available Commands

### Setup & Installation

#### `make init`
Complete initialization of the development environment.

```bash
make init
```

**What it does:**
1. Cleans any existing containers (`make clean`)
2. Builds the Docker image (`make build`)
3. Creates the Docker network (`make network`)
4. Starts the MySQL database (`make db`)
5. Starts the PHP application (`make run`)
6. Sets up the test database (`make setup-test-db`)
7. Runs migrations on the development database (`make migrate`)

**When to use:** First time setup or complete environment reset

**Output:**
```
✓ Environment initialized successfully
```

#### `make build`
Builds the Docker image for the application.

```bash
make build
```

**What it does:** Runs `docker build -t csr-membership-app .` using the Dockerfile

**When to use:** 
- After modifying `Dockerfile`
- After changing PHP configuration
- First time before running application

#### `make network`
Creates a Docker network for container communication.

```bash
make network
```

**What it does:** Creates network `csr-network` if it doesn't exist

**Note:** Automatically run by `make init`, but can be run independently

### Database

#### `make db`
Starts the MySQL database container.

```bash
make db
```

**Container Details:**
- Name: `csr-membership-db`
- Image: `mysql:8.0`
- Port: `3307:3306` (exposed on host)
- Database: `csr_membership`
- User: `user`
- Password: `password`
- Root Password: `root`

**When to use:** After `make init` when database container is needed

#### `make setup-test-db`
Configures the test database with migrations.

```bash
make setup-test-db
```

**What it does:**
1. Waits 10 seconds for MySQL to be ready
2. Creates `csr_membership_test` database
3. Grants privileges to test user
4. Runs migrations on test database with `APP_ENV=test`

**When to use:** Automatically run by `make init`, but can be run independently

**Timing:** Wait ~15 seconds after `make db` to ensure MySQL is ready

### Migrations

#### `make migrate`
Runs Doctrine migrations on the development database.

```bash
make migrate
```

**What it does:** Executes `php bin/console doctrine:migrations:migrate --no-interaction`

**When to use:**
- After pulling code with new migrations
- After creating new migrations
- In CI/CD pipelines

**Example Workflow:**
```bash
# Create a migration
sudo docker exec csr-membership-app php bin/console make:migration

# Run the migration
make migrate

# Or included in init
make init  # Automatically runs migrations
```

#### `make migrate-test`
Runs Doctrine migrations on the test database.

```bash
make migrate-test
```

**What it does:** Executes migrations with `APP_ENV=test`

**When to use:**
- Before running tests manually
- Debugging test database issues
- Usually run automatically by `make setup-test-db`

### Application

#### `make run`
Starts the PHP application container.

```bash
make run
```

**Container Details:**
- Name: `csr-membership-app`
- Network: `csr-network`
- Port: `8000:80` (exposed on host)
- Volume: Current directory mounted to `/var/www/html`
- Web Server: Apache with PHP-FPM

**When to use:** After `make init` to start the application

**Access Application:**
```bash
curl http://localhost:8000/api/users  # Without auth - returns 401
```

### Testing

#### `make test`
Runs the PHPUnit test suite.

```bash
make test
```

**What it does:** Executes `php bin/phpunit`

**Expected Output:**
```
PHPUnit 12.5.1 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.4.15
Configuration: /var/www/html/phpunit.dist.xml

[test results: . = pass, F = fail, E = error]

Time: 00:02.968, Memory: 36.50 MB

OK (22 tests, 59 assertions)
```

**Test Coverage:**
- 6 Unit Tests
- 11 Authentication Tests
- 5 User Management Tests

**When to use:**
- Before committing code
- In CI/CD pipelines
- Verifying bug fixes
- After environment setup

**Note:** Test database must be set up first (done by `make init`)

### Container Management

#### `make stop`
Stops all containers.

```bash
make stop
```

**What it does:** Stops both `csr-membership-app` and `csr-membership-db`

**When to use:**
- Pausing work without cleaning
- Freeing system resources
- Before restarting with clean state

**Note:** Data persists; containers can be restarted

#### `make clean`
Removes all containers and networks.

```bash
make clean
```

**What it does:**
1. Stops containers (`make stop`)
2. Removes containers: `csr-membership-app`, `csr-membership-db`
3. Removes network: `csr-network`

**When to use:**
- Resetting entire environment
- Cleaning up before `make init`
- Freeing disk space
- Starting completely fresh

**Warning:** Removes all data in containers. Development database will be lost.

#### `make logs`
Displays application logs.

```bash
make logs
```

**What it does:** Streams logs from `csr-membership-app` container

**When to use:**
- Debugging application issues
- Monitoring requests during testing
- Checking for errors

**Control:** Press `Ctrl+C` to stop viewing logs

### Utility Commands

#### View Makefile Targets

```bash
make help    # If implemented
cat Makefile # View all targets and descriptions
```

## Common Workflows

### Initial Setup

```bash
make init
# Wait for "Environment initialized successfully"
curl http://localhost:8000/api/auth/login -X POST \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}'
```

### Development Flow

```bash
# Morning startup
make init  # Or just 'make run' if containers exist

# During development
sudo docker exec csr-membership-app php bin/console make:migration
make migrate

# Test changes
make test

# Stop when done
make stop
```

### Testing Only

```bash
# Setup if needed
make init

# Run tests
make test

# Or specific test
sudo docker exec csr-membership-app php bin/phpunit --filter testLoginWithValidCredentials
```

### Fresh Start

```bash
# Clean everything
make clean

# Reinitialize
make init

# Ready to go
curl http://localhost:8000
```

### Database Reset

```bash
# Without losing code/containers
sudo docker exec csr-membership-app php bin/console doctrine:database:drop --if-exists --force
make migrate

# Or complete reset
make clean && make init
```

## Variables

The Makefile uses these variables (can be overridden):

```makefile
NETWORK=csr-network              # Docker network name
DB_CONTAINER=csr-membership-db   # Database container name
APP_CONTAINER=csr-membership-app # Application container name
```

Override them:
```bash
make build APP_CONTAINER=custom-app
```

## Troubleshooting

### "Permission denied" errors

Add `sudo` or add your user to docker group:
```bash
sudo usermod -aG docker $USER
# Logout and login again
```

### MySQL connection timeout

Wait longer before running commands:
```bash
make db
sleep 15  # Wait for MySQL to be ready
make setup-test-db
```

### "Port already in use"

Remove existing container:
```bash
sudo docker rm csr-membership-app csr-membership-db
make init
```

### Tests fail with "database access denied"

Reset test database permissions:
```bash
make clean
make init  # Complete reset
```

## CI/CD Integration

In your CI pipeline (GitHub Actions, GitLab CI, etc.):

```yaml
# Example: GitHub Actions
- name: Setup Environment
  run: make init

- name: Run Tests
  run: make test
```

## Performance Tips

- Use `make stop` instead of `make clean` when pausing work
- Keep Docker images pruned: `docker image prune`
- Monitor disk space: `docker system df`
- Clear old test data: `make clean && make init`

## Getting Help

View this file:
```bash
cat MAKEFILE.md
```

View Makefile targets:
```bash
grep "^[a-z].*:" Makefile | cut -d: -f1
```

Check container status:
```bash
docker ps         # Running containers
docker ps -a      # All containers
docker network ls # Available networks
```
