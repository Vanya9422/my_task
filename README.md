# My Task Project

This project demonstrates the use of RabbitMQ, MariaDB, and ClickHouse for processing URLs and storing data.

## Prerequisites

- Docker
- Docker Compose

## Getting Started

1. Clone this repository:
   ```bash
   git clone https://github.com/yourusername/my-task.git
   cd my-task

Server: mariadb
Username: root
Password: password
Database: Specify your MARIADB_DATABASE from .env

2. Run Docker Build 
   ```bash 
   docker-compose up -d && docker-compose exec php-fpm composer install
   
3. Go to page http://localhost