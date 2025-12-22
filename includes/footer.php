<footer class="footer">
    <div class="footer-content">
        <p>&copy; <?= date('Y') ?> Performance Evaluation System. All rights reserved.</p>
        <nav class="footer-nav">
            <a href="/">Home</a>
            <a href="/about.php">About</a>
            <a href="/contact.php">Contact</a>
        </nav>
    </div>
</footer>

<style>
    .footer {
        background-color: #f8f9fa;
        padding: 20px 0;
        text-align: center;
        border-top: 1px solid #e9ecef;
    }

    .footer-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    .footer-nav {
        margin-top: 10px;
    }

    .footer-nav a {
        margin: 0 10px;
        color: #007bff;
        text-decoration: none;
        transition: color 0.3s;
    }

    .footer-nav a:hover {
        color: #0056b3;
    }
</style>
</body>
</html>