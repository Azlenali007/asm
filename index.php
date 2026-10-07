<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Main Index / Public Landing Page & Service Explorer
 */

require_once __DIR__ . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

$isLoggedIn = Auth::check();
$user = Auth::user();

// Fetch categories & active services
$categories = [];
$services = [];
try {
    $categories = Database::fetchAll("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC");
    $services = Database::fetchAll("SELECT s.*, c.name as category_name FROM services s JOIN categories c ON c.id = s.category_id WHERE s.status = 1 AND c.status = 1 ORDER BY c.sort_order ASC, s.sort_order ASC");
} catch (\Exception $e) {
    // If not yet connected or tables not present
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexSMM - The #1 Enterprise Social Media Marketing Panel</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <!-- Alpine.js & ApexCharts for Interactive Real-Time UI -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="theme-dark" x-data="{ mobileNav: false, selectedCategory: 'all', search: '' }">

    <!-- Top Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="brand-logo">
                <div class="logo-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                </div>
                <span>Apex<span>SMM</span></span>
            </a>

            <div class="nav-links">
                <a href="#services" class="nav-link active">Services</a>
                <a href="/api/v2.php?action=services" class="nav-link">API Docs</a>
                <a href="#features" class="nav-link">Why Apex</a>
                <a href="#faq" class="nav-link">FAQ</a>
            </div>

            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="/user/dashboard.php" class="btn btn-primary">
                        <span>Dashboard</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                <?php else: ?>
                    <a href="/login.php" class="btn btn-ghost">Sign In</a>
                    <a href="/register.php" class="btn btn-primary">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero-section">
        <div class="hero-content">
            <div class="hero-badge">
                <span class="badge-dot"></span>
                <span>Automated API Delivery &bull; 99.99% Uptime</span>
            </div>
            <h1 class="hero-title">High-Velocity Social Growth for Resellers & Agencies</h1>
            <p class="hero-subtitle">Access direct provider wholesale rates for Instagram, YouTube, TikTok, Telegram and Twitter. Automated order fulfillment with automated refill and instant API.</p>
            
            <div class="hero-cta-group">
                <a href="/register.php" class="btn btn-primary btn-lg">Create Free Account</a>
                <a href="#services" class="btn btn-secondary btn-lg">Browse 100+ Services</a>
            </div>

            <div class="hero-stats-grid">
                <div class="stat-box">
                    <div class="stat-number">2.4M+</div>
                    <div class="stat-label">Orders Completed</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">0.02s</div>
                    <div class="stat-label">API Response Time</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">99.8%</div>
                    <div class="stat-label">Order Success Rate</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Dedicated Support</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Services Catalog Section with Alpine filtering -->
    <section id="services" class="section-container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Live Service Catalog & Wholesale Rates</h2>
                <p class="section-desc">Updated in real-time. Transparent pricing per 1,000 units with instant dispatch.</p>
            </div>
            
            <div class="search-filter-box">
                <input type="text" x-model="search" placeholder="Search service name, ID, platform..." class="input-search">
            </div>
        </div>

        <!-- Category Filters -->
        <div class="category-tabs">
            <button class="tab-btn" :class="{ 'active': selectedCategory === 'all' }" @click="selectedCategory = 'all'">All Services</button>
            <?php foreach ($categories as $cat): ?>
                <button class="tab-btn" :class="{ 'active': selectedCategory === '<?= $cat['id'] ?>' }" @click="selectedCategory = '<?= $cat['id'] ?>'">
                    <?= htmlspecialchars($cat['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Services Table -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th>Service Name</th>
                            <th>Rate / 1K</th>
                            <th>Min / Max</th>
                            <th>Avg Time</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($services)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    Services will appear here once configured. You can seed them via the installer or admin panel.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($services as $srv): ?>
                                <tr x-show="(selectedCategory === 'all' || selectedCategory === '<?= $srv['category_id'] ?>') && ('<?= strtolower(addslashes($srv['name'])) ?>'.includes(search.toLowerCase()) || '<?= $srv['id'] ?>'.includes(search))">
                                    <td class="cell-id">#<?= $srv['id'] ?></td>
                                    <td>
                                        <div class="service-name-text"><?= htmlspecialchars($srv['name']) ?></div>
                                        <div class="service-badges">
                                            <?php if ($srv['refill']): ?>
                                                <span class="badge badge-success">Auto-Refill</span>
                                            <?php endif; ?>
                                            <?php if ($srv['cancel']): ?>
                                                <span class="badge badge-info">Cancelable</span>
                                            <?php endif; ?>
                                            <span class="badge badge-muted"><?= htmlspecialchars($srv['category_name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="cell-rate"><?= rate_per_k($srv['rate']) ?></td>
                                    <td class="cell-qty"><?= number_format($srv['min_quantity']) ?> / <?= number_short($srv['max_quantity']) ?></td>
                                    <td class="cell-time">Instant (0-15m)</td>
                                    <td style="text-align: right;">
                                        <a href="/login.php" class="btn btn-sm btn-primary">Order</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-brand">
                <div class="brand-logo">
                    <div class="logo-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
                    <span>Apex<span>SMM</span></span>
                </div>
                <p>Enterprise social media marketing infrastructure powered by high performance PHP 8.3 & PDO.</p>
            </div>
            <div class="footer-links">
                <div>
                    <h4>Platform</h4>
                    <a href="/login.php">Sign In</a>
                    <a href="/register.php">Register</a>
                    <a href="#services">Services</a>
                </div>
                <div>
                    <h4>Developers</h4>
                    <a href="/api/v2.php?action=services">API Documentation</a>
                    <a href="/install/index.php">Setup Wizard</a>
                </div>
                <div>
                    <h4>Support</h4>
                    <a href="/login.php">Tickets</a>
                    <a href="#">Terms of Service</a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; <?= date('Y') ?> ApexSMM Enterprise. All rights reserved.
        </div>
    </footer>

</body>
</html>
