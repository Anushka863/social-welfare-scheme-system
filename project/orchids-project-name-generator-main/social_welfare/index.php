<?php
/**
 * Landing Page - Social Welfare Scheme Management System
 */
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? 'admin_portal.php' : 'dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Welfare Scheme Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="landing-page">
<div class="bg-animated"></div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg px-4 py-3" style="background:rgba(0,0,0,0.2);backdrop-filter:blur(10px);border-bottom:1px solid rgba(255,255,255,0.1);">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
        <div class="brand-icon" style="width:38px;height:38px;font-size:16px;">
            <i class="fas fa-hands-helping"></i>
        </div>
        <span style="font-weight:700;color:#fff;font-size:15px;">SocialWelfare</span>
    </a>
    <div class="ms-auto d-flex gap-2">
        <a href="login.php" class="btn btn-glass-outline">
            <i class="fas fa-sign-in-alt me-2"></i>Login
        </a>
        <a href="register.php" class="btn btn-glass">
            <i class="fas fa-user-plus me-2"></i>Register
        </a>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fas fa-star"></i>
            <span>Government Welfare Portal · DBMS Project 2026</span>
        </div>
        <h1 class="hero-title">Social Welfare<br><span style="background:linear-gradient(135deg,#a78bfa,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">Scheme Management</span></h1>
        <p class="hero-subtitle">A unified digital platform to discover, apply, and track government welfare schemes. Empowering citizens with transparent access to benefits.</p>
        <div class="hero-buttons">
            <a href="register.php" class="btn btn-glass px-5 py-3" style="font-size:16px;">
                <i class="fas fa-rocket me-2"></i>Get Started
            </a>
            <a href="login.php" class="btn btn-glass-outline px-5 py-3" style="font-size:16px;">
                <i class="fas fa-sign-in-alt me-2"></i>Login
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 px-4">
    <div class="container">
        <div class="text-center mb-5 animate-on-scroll">
            <h2 style="font-size:32px;font-weight:800;color:#fff;">Why Use This Portal?</h2>
            <p style="color:rgba(255,255,255,0.6);font-size:16px;max-width:500px;margin:12px auto 0;">Everything you need to access government welfare benefits in one place.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4 animate-on-scroll delay-1">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-th-list text-white"></i></div>
                    <h5 style="color:#fff;font-weight:700;margin-bottom:8px;">Browse Schemes</h5>
                    <p style="color:rgba(255,255,255,0.6);font-size:14px;margin:0;">Explore a wide range of government welfare schemes across agriculture, education, health, housing and more.</p>
                </div>
            </div>
            <div class="col-md-4 animate-on-scroll delay-2">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-clipboard-check text-white"></i></div>
                    <h5 style="color:#fff;font-weight:700;margin-bottom:8px;">Easy Application</h5>
                    <p style="color:rgba(255,255,255,0.6);font-size:14px;margin:0;">Apply for schemes online with document upload. Get a unique application ID (SW2026-XXXX) instantly.</p>
                </div>
            </div>
            <div class="col-md-4 animate-on-scroll delay-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-search-location text-white"></i></div>
                    <h5 style="color:#fff;font-weight:700;margin-bottom:8px;">Track Status</h5>
                    <p style="color:rgba(255,255,255,0.6);font-size:14px;margin:0;">Real-time tracking of your application status. Get notified when your application is approved or needs review.</p>
                </div>
            </div>
            <div class="col-md-4 animate-on-scroll delay-1">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-shield-alt text-white"></i></div>
                    <h5 style="color:#fff;font-weight:700;margin-bottom:8px;">Secure & Verified</h5>
                    <p style="color:rgba(255,255,255,0.6);font-size:14px;margin:0;">Password hashing, prepared statements, role-based access control. Your data is safe with us.</p>
                </div>
            </div>
            <div class="col-md-4 animate-on-scroll delay-2">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-magic text-white"></i></div>
                    <h5 style="color:#fff;font-weight:700;margin-bottom:8px;">Eligibility Checker</h5>
                    <p style="color:rgba(255,255,255,0.6);font-size:14px;margin:0;">Auto-check your eligibility before applying. Save time by knowing which schemes you qualify for.</p>
                </div>
            </div>
            <div class="col-md-4 animate-on-scroll delay-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-tachometer-alt text-white"></i></div>
                    <h5 style="color:#fff;font-weight:700;margin-bottom:8px;">Admin Panel</h5>
                    <p style="color:rgba(255,255,255,0.6);font-size:14px;margin:0;">Full admin dashboard with analytics, scheme management, application review, and bulk approval tools.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-5 px-4">
    <div class="container">
        <div class="glass-card p-5">
            <div class="row g-4 text-center">
                <div class="col-6 col-md-3 animate-on-scroll">
                    <div style="font-size:40px;font-weight:800;color:#fff;" class="counter-animate" data-target="6">0</div>
                    <div style="color:rgba(255,255,255,0.6);font-size:14px;margin-top:4px;">Active Schemes</div>
                </div>
                <div class="col-6 col-md-3 animate-on-scroll delay-1">
                    <div style="font-size:40px;font-weight:800;color:#fff;" class="counter-animate" data-target="100" data-suffix="+">0</div>
                    <div style="color:rgba(255,255,255,0.6);font-size:14px;margin-top:4px;">Citizens Benefited</div>
                </div>
                <div class="col-6 col-md-3 animate-on-scroll delay-2">
                    <div style="font-size:40px;font-weight:800;color:#fff;" class="counter-animate" data-target="5">0</div>
                    <div style="color:rgba(255,255,255,0.6);font-size:14px;margin-top:4px;">Departments</div>
                </div>
                <div class="col-6 col-md-3 animate-on-scroll delay-3">
                    <div style="font-size:40px;font-weight:800;color:#fff;" class="counter-animate" data-target="98" data-suffix="%">0</div>
                    <div style="color:rgba(255,255,255,0.6);font-size:14px;margin-top:4px;">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Footer -->
<footer class="py-5 text-center" style="border-top:1px solid rgba(255,255,255,0.1);">
    <div class="container">
        <h3 style="color:#fff;font-weight:700;margin-bottom:8px;">Ready to get started?</h3>
        <p style="color:rgba(255,255,255,0.55);font-size:15px;margin-bottom:24px;">Join thousands of citizens accessing welfare benefits online.</p>
        <a href="register.php" class="btn btn-glass px-5 py-3" style="font-size:16px;">
            <i class="fas fa-user-plus me-2"></i>Create Free Account
        </a>
        <div style="margin-top:40px;color:rgba(255,255,255,0.3);font-size:12px;">
            &copy; 2026 Social Welfare Scheme Management System · Built for DBMS Final Year Project
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
</body>
</html>
