# Installation Guide

This guide provides developers with detailed instructions on how to install Project Name and gain an understanding of its code structure.

## Prerequisites

Before you begin, ensure you have the following prerequisites:

- PHP 8.2
- Composer
- Node.js 18.17.1 
- Local development environment (XAMPP, Laragon, MAMP)

## Installation Steps

1. **Clone the Repository:** Start by cloning the repository to your local machine:
   ```sh
   git clone https://github.com/shimatachi/Laravel-9-Project
   cd Laravel-9-Project
2. **Install Composer dependencies:** Run the following command to install the required PHP dependencies:
   ```sh
   composer install
3. **Copy the .env File:** Create a copy of the .env.example file and name it **.env**
   ```sh
   cp .env.example .env
4. **Generate an application key:** Run the following command to generate a unique application key: 
   ```sh
   php artisan key:generate
5. **Configure Database:** Open the .env file and set the database connection details, including the database name, username, and password.

6. **Run database migrations:** Apply the database migrations to create the required tables:
   ```sh
   php artisan migrate
7. **Create Symbolic Link for Storage:** Run the following command to create a symbolic link from the public/storage directory to the storage/app/public directory:
   ```sh
   php artisan storage:link
8. **Install Node.js dependencies:** Install the JavaScript dependencies using npm
   ```sh
   npm install
9. **Compile assets for development:** Compile the frontend assets using the following command:
   ```sh
   npm run dev
9. **Start the development server:** Run the following command to start the development server: 
   ```sh
   php artisan serve
11. **Accessing the web application at the provided URL**

# Code Structure

Understanding the code structure of Project Name is essential for effectively contributing to, maintaining, or customizing the software. This section provides an overview of the main directories and components in the codebase.

## Directory Overview

The codebase of Project Name follows a modular structure to promote separation of concerns and maintainability. Here's an overview of the main directories:

- `app`: Contains the core application logic, including models, controllers, and services.
- `config`: Houses configuration files for various components such as database connections and application settings.
- `database`: Includes migration files and seeders for database setup and population.
- `resources`: Holds assets, views, and frontend components like CSS, JavaScript, and Blade templates.
- `routes`: Defines the application's routes, including web and API routes.
- `tests`: Contains test suites and unit tests for automated testing.
- `public`: Serves as the web server's document root, containing publicly accessible files like images and compiled assets.

## Key Components

### Models

Models, located in the `app` directory, represent database tables and interact with data. They define relationships and perform data validation.

### Controllers

Controllers in the `app/Http/Controllers` directory handle incoming HTTP requests and interact with models to retrieve and manipulate data.

### Views

Views, located in the `resources/views` directory, contain the HTML templates that determine the presentation of web pages.

### Routes

Routes are defined in the `routes` directory, with web routes in `web.php` and API routes in `api.php`. Routes map URLs to controller actions.

### Migrations

Migrations, found in the `database/migrations` directory, define changes to the database schema. They're used for creating and modifying tables.

### Tests

The `tests` directory contains PHPUnit test suites that help ensure the correctness of the application's functionalities.

## Customization and Contribution

Understanding the code structure empowers you to customize and contribute to Project Name effectively. Feel free to explore each directory to gain a deeper understanding of the application's architecture.

For detailed information about specific functions, methods, or features, refer to the code comments and documentation throughout the codebase.

For more information on using Project Name's features, please refer to the [User Manual](../manual/admin/README.md).

By grasping the code structure, you'll be better equipped to enhance, debug, and build upon Project Name.

