<?php
session_start();
require_once 'config/constants.php';

// Redirect logged-in users
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header('Location: ' . BASE_URL . 'admin/dashboard.php');
            break;
        case 'teacher':
            header('Location: ' . BASE_URL . 'teacher/dashboard.php');
            break;
        case 'student':
            header('Location: ' . BASE_URL . 'student/student_dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . 'login.php');
            break;
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ASTU Performance Evaluation System</title>
<link rel="stylesheet" href="..assets/images/logo.png">
<style>
/* Reset */
* { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins', sans-serif; }
body { background:#0d1117; color:#fff; overflow-x:hidden; }

/* Header */
header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:20px 50px;
    background:rgba(0,0,0,0.7);
    position:fixed;
    width:100%;
    top:0;
    z-index:1000;
    backdrop-filter:blur(10px);
}
.logo-container {
    display:flex;
    align-items:center;
    gap:15px;
}
.logo-container img {
    height:60px;
    width:auto;
    border-radius:10px;
    box-shadow:0 5px 20px rgba(0,0,0,0.3);
}
header h2 { font-size:1.8em; font-weight:700; color:#fff; }
header a {
    text-decoration:none;
    color:#fff;
    background:#ff6b6b;
    padding:10px 30px;
    border-radius:50px;
    font-weight:600;
    transition:0.3s;
}
header a:hover { background:#ff4757; }

/* Hero Section */
.hero {
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    height:90vh;
    text-align:center;
    padding:0 20px;
    background:linear-gradient(135deg,#667eea,#764ba2);
    position:relative;
    overflow:hidden;
    margin-top:80px;
}
.hero h1 {
    font-size:3em;
    font-weight:700;
    margin-bottom:20px;
    text-shadow: 2px 2px 15px rgba(0,0,0,0.4);
}
.hero p {
    font-size:1.2em;
    max-width:700px;
    line-height:1.8em;
    margin-bottom:40px;
    color:#f0f0f0;
}
.hero .btn-login {
    text-decoration:none;
    background:#fff;
    color:#764ba2;
    padding:15px 50px;
    border-radius:50px;
    font-weight:600;
    font-size:1.1em;
    box-shadow:0 10px 25px rgba(0,0,0,0.3);
    transition:all 0.3s ease;
}
.hero .btn-login:hover {
    transform:translateY(-5px);
    background:#f0f0f0;
    color:#667eea;
    box-shadow:0 15px 35px rgba(0,0,0,0.4);
}

/* Animated floating shapes in hero */
.hero::before, .hero::after {
    content:'';
    position:absolute;
    border-radius:50%;
    background:rgba(255,255,255,0.05);
    animation:float 12s linear infinite;
}
.hero::before {
    width:500px; height:500px;
    top:-100px; left:-150px;
}
.hero::after {
    width:400px; height:400px;
    bottom:-80px; right:-100px;
    animation-direction:reverse;
}
@keyframes float {
    0% { transform:translateY(0px) translateX(0px); }
    50% { transform:translateY(40px) translateX(30px); }
    100% { transform:translateY(0px) translateX(0px); }
}

/* Features Section */
.features {
    display:flex;
    justify-content:space-around;
    flex-wrap:wrap;
    gap:30px;
    padding:80px 20px;
    background:#0d1117;
}
.feature-card {
    background:rgba(255,255,255,0.05);
    border-radius:20px;
    padding:35px;
    flex:1 1 300px;
    text-align:center;
    transition:transform 0.5s, box-shadow 0.5s;
    cursor:pointer;
    backdrop-filter:blur(10px);
}
.feature-card:hover {
    transform:translateY(-15px) scale(1.05);
    box-shadow:0 20px 50px rgba(0,0,0,0.4);
}
.feature-card h3 { margin-bottom:15px; font-size:1.5em; }
.feature-card p { color:#ddd; font-size:1em; line-height:1.6em; }

/* Footer */
footer {
    padding:20px;
    text-align:center;
    font-size:0.9em;
    background:rgba(0,0,0,0.7);
    color:#ccc;
    backdrop-filter:blur(10px);
}

@media(max-width:768px){
    header { flex-direction:column; gap:15px; padding:15px 20px; }
    .hero h1 { font-size:2.2em; }
    .features { flex-direction:column; }
}
</style>
</head>
<body>

<!-- Header -->
<header>
    <div class="logo-container">
        <!-- Logo File Location: Place your uploaded logo in "assets/images/logo.png" -->
        <img src="assets/images/logo.png" alt="ASTU Logo">
        <h2>ASTU Evaluation System</h2>
    </div>
    <a href="<?= BASE_URL ?>login.php">Login</a>
</header>

<!-- Hero Section -->
<section class="hero">
    <h1>Welcome to ASTU Performance Evaluation System</h1>
    <p>
        ASTU uses this system to efficiently manage teacher evaluations, track student feedback, and generate admin reports. 
        Designed for Students, Teachers, and Administrators for smooth academic evaluation processes.
    </p>
    <a href="<?= BASE_URL ?>login.php" class="btn-login">Login</a>
</section>

<!-- Features Section -->
<section class="features">
    <div class="feature-card">
        <h3>Student Feedback</h3>
        <p>Students can provide anonymous feedback to improve teaching quality.</p>
    </div>
    <div class="feature-card">
        <h3>Teacher Dashboard</h3>
        <p>Teachers can view evaluations and improve their teaching efficiency.</p>
    </div>
    <div class="feature-card">
        <h3>Admin Reports</h3>
        <p>Administrators can generate detailed performance reports for academic improvements.</p>
    </div>
</section>

<!-- Footer -->
<footer>
    &copy; <?= date('Y') ?> Assosa University (ASTU) - Performance Evaluation System. All rights reserved.
</footer>

<!-- JS Animations -->
<script>
    // Hero parallax effect
    const hero = document.querySelector('.hero');
    document.addEventListener('mousemove', e => {
        const x = (window.innerWidth/2 - e.pageX)/50;
        const y = (window.innerHeight/2 - e.pageY)/50;
        hero.style.transform = `rotateY(${x}deg) rotateX(${y}deg)`;
    });

    // Scroll animations for feature cards
    const cards = document.querySelectorAll('.feature-card');
    window.addEventListener('scroll', () => {
        const trigger = window.innerHeight * 0.85;
        cards.forEach(card => {
            const top = card.getBoundingClientRect().top;
            if(top < trigger){
                card.style.transform = 'translateY(0) scale(1)';
                card.style.opacity = '1';
            }
        });
    });
</script>

</body>
</html>
