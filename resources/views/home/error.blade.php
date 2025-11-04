<?php
$brand50 = '#ecf9f4';
$brand500 = '#36af81';
$brand900 = '#06130e';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Something Went Wrong</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="error-container">
        <div class="h3 error-title">Oops! Something Went Wrong</div>
        <br>
        <i class="fas fa-exclamation-circle error-icon"></i>
        <br>
        <br>
        <p class="error-message my-4 h2">{{ $message }}</p>
        @if (currentUser())
        <div>
            <a href="/dashboard" class="btn btn-primary btn-outline btn-home">
                <i class="fas fa-logout"></i> Dashboard
            </a>
            <a href="{{ route('logout') }}" class="btn btn-brand btn-home">
                <i class="fas fa-logout"></i> Logout
            </a>
        </div>
        @else
            <a href="/" class="btn btn-brand btn-home">
                <i class="fas fa-home me-2"></i> Login
            </a>
        @endif
    </div>

    <!-- Bootstrap 5 JS (Optional, for any interactive components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
<style>
        body {
            /* background-color: #f8f9fa; */
            background-color: {{ $brand50 }};
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Arial', sans-serif;
        }
        .error-container {
            text-align: center;
            max-width: 600px;
            padding: 20px;
        }
        .error-icon {
            font-size: 5rem;
            /* color: #dc3545; */
            animation: pulse 2s infinite;
            color: {{ $brand500 }};
        }
        .error-title {
            font-size: 1.5rem;
            /* color: #343a40; */
            color: #6c757d;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.9rem;
            margin-bottom: 2rem;
            color: {{ $brand900 }};
        }
        .btn-home {
            padding: 10px 30px;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .btn-brand {
            background-color: <?= $brand500 ?>;
            border-color: <?= $brand500 ?>;
            color: #fff;
        }
        .btn-brand:hover {
            background-color: <?= $brand900 ?>;
            border-color: <?= $brand900 ?>;
            color: #fff;
        }
    </style>
</html>