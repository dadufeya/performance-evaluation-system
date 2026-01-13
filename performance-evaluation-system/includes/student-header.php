<?php require_once __DIR__ . '/../config/constants.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | PES</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --std-primary: #6366f1; /* Indigo */
            --std-glass: rgba(255, 255, 255, 0.9);
            --sidebar-width: 260px;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .student-header {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: 70px;
            background: var(--std-primary); /* Indigo background */
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header-left h2 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .profile-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px 15px;
            background: white;
            color: var(--std-primary);
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
        }

        .std-avatar {
            width: 35px;
            height: 35px;
            background: var(--std-primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header class="student-header">
        <div class="header-left">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Student'); ?></h2>
        </div>
        <div class="header-right">
            <div class="profile-pill">
                <div class="std-avatar">
                    <?= strtoupper($_SESSION['username'][0] ?? 'S'); ?>
                </div>
                <span>Profile</span>
            </div>
        </div>
    </header>
</body>
</html>