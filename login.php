<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            background-color: #222;
            color: white;
            font-family: Arial, sans-serif;
            text-align: center;
            padding-top: 10%;
        }
        .container {
            background-color: #333;
            padding: 2rem;
            border-radius: 8px;
            display: inline-block;
        }
        input {
            padding: 0.5rem;
            margin: 0.5rem 0;
            border-radius: 4px;
            border: none;
        }
        button {
            padding: 0.75rem 1.5rem;
            background-color: #3AAA35;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #2e7d28;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sign In</h1>
        <form method="POST" action="login_process.php">
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
