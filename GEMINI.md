# GEMINI.md: Project EduManager

This document provides a comprehensive overview of the EduManager project, its architecture, and development conventions to be used as instructional context for future interactions.

## 1. Project Overview

EduManager is a monolithic web application designed as an all-in-one platform for educational institutions. It provides a rich set of features for managing students, admissions, academic results, exams, attendance, and more.

This project has a detailed documentation of all its features and structure in `public/feature-docs/`.

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

### Project Structure & Module Organization

- **Backend Core**: Located in `app/` (Actions, Jobs, Models, Rules, Helpers). HTTP entry points are in `routes/web.php`, `routes/api.php`, and feature-specific route files under `routes/`.
- **Frontend Source**: Inertia + React/TypeScript under `resources/js`. Uses Chakra UI, shared components/hooks, and Vite entry at `resources/js/app.tsx`. Styles start in `resources/css/app.css`.
- **Database Assets**: Found in `database/migrations`, `database/seeders`, and `database/factories`. Seeders include roles and sample domain data.
- **Public & Build**: Public entry is `public/index.php`. Built assets are published to `public/build` by Vite.
- **Feature Documentation**: The project knowledge base is located in `public/feature-docs/`. Start with `features.html` for the implemented feature map, and use `backend.html`, `frontend.html`, and `data.html` for detailed references. Always keep these docs updated when adding or changing features.

## 2. Building and Running

### Environment Setup

1.  Copy the example environment file: `cp .env.example .env`
2.  Fill in the necessary environment variables in the `.env` file, especially for the database (`DB_*`), application key (`APP_KEY`), and any payment/AWS credentials.
3.  Generate the application key: `php artisan key:generate`

### Backend (Laravel)

- **Install Dependencies:**
  ```bash
  ./vendor/bin/sail composer install
  ```
- **Run Database Migrations:**
  ```bash
  ./vendor/bin/sail artisan migrate
  ```
- **Start the Development Server:**
  ```bash
  ./vendor/bin/sail artisan serve
  ```

### Frontend (React & Vite)

- **Install Dependencies:**
  ```bash
  ./vendor/bin/sail npm install
  ```
- **Run the Development Server (with Hot Module Replacement):**
  ```bash
  ./vendor/bin/sail npm run dev
  ```
- **Build for Production:**
  ```bash
  ./vendor/bin/sail npm run build
  ```

## 3. Development Conventions

### Code Style & Naming Conventions

The project enforces strict code styles to maintain consistency.

- **Frontend (TypeScript/React):**
  - **Linter:** ESLint (`.eslintrc.js`). Forbids `console` and enforces strict equality.
  - **Formatter:** Prettier (`.prettierrc.json`). Uses single quotes and 2-space tabs.
  - **Naming:** Components in `PascalCase`, hooks prefixed with `use*`, props/interfaces in `UpperCamelCase`.
  - **Run Linter & Type Checker:**
    ```bash
    ./vendor/bin/sail npm run lint
    ```
- **Backend (PHP):**
  - **Style:** PSR-12 compliant. Formatted with Laravel Pint.
  - **Naming:** `StudlyCaps` for classes, `camelCase` for methods/properties, and typed properties/returns where possible.
  - **Architecture:** Prefer DTO/Action patterns already in `app/Actions` and reuse shared helpers (`app/helpers.php`) before adding new globals.
  - **Run Formatter:**
    ```bash
    ./vendor/bin/pint
    ```

### Testing Guidelines

The project uses **Pest** for backend testing. Test files are located in the `tests/` directory, separated into `Feature` and `Unit` suites.

- Write Pest feature tests alongside the code in `tests/Feature`; leverage factories and seeders to keep fixtures consistent.
- Use database transactions (default in `tests/Pest.php`) and seed required roles/permissions; avoid depending on production `.env`.
- Cover new endpoints, commands, and business rules; for UI-facing changes, add minimal server assertions (HTTP status, inertia props, authorization).
- **Run all tests:**
  ```bash
  ./vendor/bin/sail pest
  ```

### Commit & Pull Request Guidelines

- Keep commit messages short and action-led, matching current history (e.g., `whatsapp messaging capability`, `fixes`). Prefer present-tense imperatives like `add`, `fix`, `refactor`.
- For PRs, include a summary of the change, linked issue/ticket, test command(s) run, and screenshots/GIFs for UI tweaks.
- Call out migrations or environment changes explicitly so reviewers can apply them.

## 4. Important Note

- Before you perform any task, Study the style, theme, structure and convention followed in this project.
- Your implementation should follow this structure and coding style,
- Try to reuse what has already been defined, but when you need to create one, Make your code resuable and
- If you have any questions or need clarifications, ask the questions at once before you start. Responses will be provided to all your questions.
- Where possible, Make reasonable assumptions where necessary
- Add wide covering tests for every feature you implement, following the style and structure of the existing tests.
- When features are added or updated, the documentation should be updated to reflect the changes. The documentation should at all times contain the latest detailed information about the project.
