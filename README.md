# Cleverpath PHP Project

## Setup Instructions

### 1. Install Dependencies
```sh
composer install
```

### 2. Configure Environment
- Copy `config/database.php.example` to `config/database.php` and set your DB credentials.

### 3. Run Local Server
```sh
php -S localhost:8000 -t public
```

### 4. WebSocket Setup (for real-time chat)
```sh
php web_server.php
```

### 5. Deploying
- Use a PHP hosting provider that supports WebSockets and MySQL.

