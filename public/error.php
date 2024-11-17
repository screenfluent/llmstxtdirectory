<!DOCTYPE html>
<html>
<head>
    <title>Error - <?= SITE_NAME ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Space Grotesk', sans-serif;
            margin: 0;
            padding: 20px;
            background: #FFF;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .error-container {
            max-width: 600px;
            padding: 40px;
            text-align: center;
            background: #FAFAFA;
            border: 1px solid #E3E3E3;
            border-radius: 10px;
        }
        h1 {
            color: #333;
            margin: 0 0 20px;
        }
        p {
            color: #666;
            margin: 0 0 20px;
            line-height: 1.5;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #333;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #444;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Oops! Something went wrong.</h1>
        <p>We encountered an error while processing your request. Our team has been notified and will look into it.</p>
        <a href="/" class="btn">Return Home</a>
    </div>
</body>
</html>
