# GEMINI.md: Project EduManager

This document provides a comprehensive overview of the EduManager project, its architecture, and development conventions to be used as instructional context for future interactions.

## 1. Project Overview

EduManager is a monolithic web application designed as an all-in-one platform for educational institutions. It provides a rich set of features for managing students, admissions, academic results, exams, attendance, and more.

- **Backend:** Built with **Laravel (PHP 8.2)**, following standard framework conventions. It handles business logic, database interactions, and authentication.
- **Frontend:** A modern **React** single-page application (SPA) written in **TypeScript**.
- **Architecture:** The project uses **Inertia.js** to tightly couple the Laravel backend with the React frontend. This allows for building a modern SPA experience without creating a separate API.
- **UI:** The user interface is built using the **Chakra UI** component library.
- **Database:** The application is configured to use **MySQL**.
- **Key Dependencies:**
  - `spatie/laravel-permission`: For role and permission management.
  - `phpoffice/phpspreadsheet`: For handling Excel file imports/exports (e.g., result sheets).
  - `league/flysystem-aws-s3-v3`: For file storage on Amazon S3.
  - `sentry/sentry-laravel`: For error monitoring and reporting.
  - `Paystack`, `Monnify`: Integrations for payment processing.

## 2. Building and Running

### Environment Setup

1.  Copy the example environment file: `cp .env.example .env`
2.  Fill in the necessary environment variables in the `.env` file, especially for the database (`DB_*`), application key (`APP_KEY`), and any payment/AWS credentials.
3.  Generate the application key: `php artisan key:generate`

### Backend (Laravel)

- **Install Dependencies:**
  ```bash
  composer install
  ```
- **Run Database Migrations:**
  ```bash
  sail artisan migrate
  ```
- **Start the Development Server:**
  ```bash
  sail artisan serve
  ```

### Frontend (React & Vite)

- **Install Dependencies:**
  ```bash
  sail npm install
  ```
- **Run the Development Server (with Hot Module Replacement):**
  ```bash
  sail npm run dev
  ```
- **Build for Production:**
  ```bash
  sail npm run build
  ```

## 3. Development Conventions

### Testing

The project uses both **PHPUnit** and **Pest** for backend testing. Test files are located in the `tests/` directory, separated into `Feature` and `Unit` suites.

- **Run all tests:**
  ```bash
  sail pest
  ```

### Code Style & Linting

The project enforces code style to maintain consistency.

- **Frontend (TypeScript/React):**
  - **Linter:** ESLint (`.eslintrc.js`)
  - **Formatter:** Prettier (`.prettierrc.json`)
  - **Run Linter & Type Checker:**
    ```bash
    sail npm run lint
    ```
- **Backend (PHP):**
  - **Style:** Laravel Pint is configured. While no explicit script is in `composer.json`, it can be run manually:
    ```bash
    ./vendor/bin/pint
    ```
