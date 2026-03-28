<?php
session_start();
include '../../../config.php';

// Unset the current exam session if a violation occurred
unset($_SESSION['current_ngn_examTaken']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Violation — Studium</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #0a1628;
            --danger: #ef4444;
            --surface: #ffffff;
            --text-muted: #64748b;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            text-align: center;
            padding: 20px;
        }
        .violation-card {
            background: var(--surface);
            padding: 48px;
            border-radius: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.05);
            max-width: 500px;
            border: 1px solid #e2e8f0;
        }
        .icon {
            font-size: 64px;
            color: var(--danger);
            margin-bottom: 24px;
        }
        h1 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--primary);
        }
        p {
            color: var(--text-muted);
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        .btn-home {
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            display: inline-block;
            transition: all 0.2s ease;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="violation-card">
        <div class="icon"><i class="fas fa-user-shield"></i></div>
        <h1>Security Protocol Initiated</h1>
        <p>
            Your examination has been automatically terminated because we detected an attempt to 
            <strong>switch tabs, minimize the window, or click outside the page</strong>. 
            Studium practice exams follow strict NCLEX security standards to ensure clinical integrity.
        </p>
        <a href="../index.php" class="btn-home">Return to Dashboard</a>
    </div>
</body>
</html>
