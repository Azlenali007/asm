<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * User: New Order Wizard
 */

require_once dirname(__DIR__) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    redirect('/login.php');
}

$user = Auth::user();

// Fetch active categories and services
$categories = Database::fetchAll("SELECT * FROM categories WHERE status = 1 ORDER BY sort_order ASC");
$services = Database::fetchAll("SELECT * FROM services WHERE status = 1 ORDER BY sort_order ASC");

// Custom rates if any
$customRates = !empty($user['custom_rates']) ? json_decode($user['custom_rates'], true) : [];

// Prepare services JSON for reactive Alpine client logic
$servicesJson = [];
foreach ($services as $s) {
    $rate = (float)$s['rate'];
    if (isset($customRates[$s['id']])) {
        $rate = (float)$customRates[$s['id']];
    }
    $servicesJson[] = [
        'id'          => (int)$s['id'],
        'category_id' => (int)$s['category_id'],
        'name'        => $s['name'],
        'type'        => $s['type'],
        'rate'        => $rate,
        'min'         => (int)$s['min_quantity'],
        'max'         => (int)$s['max_quantity'],
        'description' => $s['description'] ?? '',
        'refill'      => (bool)$s['refill'],
        'cancel'      => (bool)$s['cancel'],
    ];
}

$error = flash('error');
$success = flash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order - ApexSMM Enterprise</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-dark panel-layout" x-data="{ sidebarOpen: false }">

    <!-- Sidebar Navigation -->
    <aside class="sidebar" :class="{ 'open': sidebarOpen }">
        <div class="sidebar-header">
            <a href="/user/dashboard.php" class="brand-logo">
                <div class="logo-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
                <span>Apex<span>SMM</span></span>
            </a>
            <button class="btn-close-sidebar" @click="sidebarOpen = false">&times;</button>
        </div>

        <div class="sidebar-user-card">
            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 2)) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= e($user['username']) ?></div>
                <div class="user-balance"><?= money($user['balance']) ?></div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="/user/dashboard.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="/user/new-order.php" class="nav-item active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
                <span>New Order</span>
            </a>
            <a href="/user/orders.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                <span>Orders History</span>
            </a>
            <a href="/user/services.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>Services List</span>
            </a>
            <a href="/user/add-funds.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 10h20"/></svg>
                <span>Add Funds</span>
            </a>
            <a href="/user/transactions.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                <span>Transactions</span>
            </a>
            <a href="/user/tickets.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                <span>Support Tickets</span>
            </a>
            <a href="/user/api.php" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                <span>API Integration</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-panel">
        <header class="topbar">
            <button class="btn-hamburger" @click="sidebarOpen = true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <div class="topbar-title">Place Single Order</div>
            <div class="topbar-actions">
                <div class="header-balance">Balance: <strong><?= money($user['balance']) ?></strong></div>
            </div>
        </header>

        <div class="panel-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= e($success) ?></div>
            <?php endif; ?>

            <!-- Alpine Order Form Component -->
            <div x-data="orderApp()" x-init="init()" class="order-grid">
                
                <!-- Order Creation Form -->
                <div class="card" style="flex: 3;">
                    <div class="card-header">
                        <h3>Order Details</h3>
                    </div>
                    <div class="card-body">
                        <form action="/actions/order/create.php" method="POST">
                            <?= csrf_field() ?>

                            <!-- Category Selection -->
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" class="form-control" x-model="selectedCategoryId" @change="onCategoryChange()">
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="cat.id" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Service Selection -->
                            <div class="form-group">
                                <label for="service">Service</label>
                                <select id="service" name="service_id" class="form-control" x-model="selectedServiceId" @change="onServiceChange()" required>
                                    <template x-for="srv in filteredServices" :key="srv.id">
                                        <option :value="srv.id" x-text="'#' + srv.id + ' - ' + srv.name + ' ($' + srv.rate.toFixed(4) + ' / 1K)'"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Target Link -->
                            <div class="form-group">
                                <label for="link">Target Link / URL</label>
                                <input type="url" id="link" name="link" class="form-control" placeholder="https://instagram.com/p/... or profile URL" required>
                                <span class="field-help">Make sure the account is public and link format is valid.</span>
                            </div>

                            <!-- Quantity Field (or Custom Comments textarea) -->
                            <div class="form-group" x-show="currentService && currentService.type !== 'custom_comments'">
                                <div style="display: flex; justify-content: space-between;">
                                    <label for="quantity">Quantity</label>
                                    <span class="field-help" x-show="currentService" x-text="'Min: ' + (currentService ? currentService.min : 0) + ' | Max: ' + (currentService ? currentService.max.toLocaleString() : 0)"></span>
                                </div>
                                <input type="number" id="quantity" name="quantity" class="form-control" x-model.number="quantity" @input="calculateCharge()" placeholder="e.g. 1000">
                            </div>

                            <!-- Custom Comments Field -->
                            <div class="form-group" x-show="currentService && currentService.type === 'custom_comments'">
                                <label for="comments">Custom Comments (1 per line)</label>
                                <textarea id="comments" name="comments" rows="5" class="form-control" x-model="comments" @input="onCommentsChange()" placeholder="Great post!&#10;Awesome content!&#10;Keep it up!"></textarea>
                                <span class="field-help" x-text="'Comments count: ' + commentsCount"></span>
                            </div>

                            <!-- Live Price Calculation Summary -->
                            <div class="charge-summary-box">
                                <div class="charge-row">
                                    <span>Rate per 1,000:</span>
                                    <strong x-text="'$' + (currentService ? currentService.rate.toFixed(4) : '0.0000')"></strong>
                                </div>
                                <div class="charge-row total">
                                    <span>Total Charge:</span>
                                    <span class="charge-amount" x-text="'$' + calculatedCharge.toFixed(4)"></span>
                                </div>
                                <div x-show="isInsufficientFunds" class="warning-text">
                                    Warning: Insufficient balance. You need <strong x-text="'$' + (calculatedCharge - userBalance).toFixed(2)"></strong> more to complete this order.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block btn-lg" :disabled="isInsufficientFunds || quantity <= 0" style="margin-top: 16px;">
                                Submit Order
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Service Specifications Sidebox -->
                <div class="card" style="flex: 2;">
                    <div class="card-header">
                        <h3>Service Specifications</h3>
                    </div>
                    <div class="card-body">
                        <template x-if="currentService">
                            <div>
                                <h4 style="font-size: 15px; margin-bottom: 12px; color: #fff;" x-text="currentService.name"></h4>
                                
                                <div class="spec-grid">
                                    <div class="spec-item">
                                        <div class="spec-label">Service ID</div>
                                        <div class="spec-value" x-text="'#' + currentService.id"></div>
                                    </div>
                                    <div class="spec-item">
                                        <div class="spec-label">Price per 1K</div>
                                        <div class="spec-value" style="color: #10b981;" x-text="'$' + currentService.rate.toFixed(4)"></div>
                                    </div>
                                    <div class="spec-item">
                                        <div class="spec-label">Minimum Limit</div>
                                        <div class="spec-value" x-text="currentService.min.toLocaleString()"></div>
                                    </div>
                                    <div class="spec-item">
                                        <div class="spec-label">Maximum Limit</div>
                                        <div class="spec-value" x-text="currentService.max.toLocaleString()"></div>
                                    </div>
                                    <div class="spec-item">
                                        <div class="spec-label">Auto Refill</div>
                                        <div class="spec-value" x-text="currentService.refill ? 'Yes (Guaranteed)' : 'No Refill'"></div>
                                    </div>
                                    <div class="spec-item">
                                        <div class="spec-label">Cancelable</div>
                                        <div class="spec-value" x-text="currentService.cancel ? 'Yes' : 'No'"></div>
                                    </div>
                                </div>

                                <div style="margin-top: 16px; border-top: 1px solid var(--border); padding-top: 14px;">
                                    <div style="font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase;">Description / Notes</div>
                                    <div style="font-size: 13px; line-height: 1.5; color: #cbd5e1; white-space: pre-line;" x-text="currentService.description || 'No special requirements for this service.'"></div>
                                </div>
                            </div>
                        </template>
                        <template x-if="!currentService">
                            <p style="color: var(--text-muted);">Select a service to view requirements.</p>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- Alpine Order Reactive Script -->
    <script>
        function orderApp() {
            return {
                userBalance: <?= (float)$user['balance'] ?>,
                categories: <?= json_encode($categories) ?>,
                allServices: <?= json_encode($servicesJson) ?>,
                selectedCategoryId: <?= !empty($categories) ? $categories[0]['id'] : 'null' ?>,
                filteredServices: [],
                selectedServiceId: null,
                currentService: null,
                quantity: 1000,
                comments: '',
                commentsCount: 0,
                calculatedCharge: 0.00,

                get isInsufficientFunds() {
                    return this.calculatedCharge > this.userBalance;
                },

                init() {
                    this.onCategoryChange();
                },

                onCategoryChange() {
                    this.filteredServices = this.allServices.filter(s => s.category_id == this.selectedCategoryId);
                    if (this.filteredServices.length > 0) {
                        this.selectedServiceId = this.filteredServices[0].id;
                        this.onServiceChange();
                    } else {
                        this.selectedServiceId = null;
                        this.currentService = null;
                        this.calculatedCharge = 0;
                    }
                },

                onServiceChange() {
                    this.currentService = this.filteredServices.find(s => s.id == this.selectedServiceId) || null;
                    if (this.currentService) {
                        this.quantity = this.currentService.min;
                    }
                    this.calculateCharge();
                },

                onCommentsChange() {
                    const lines = this.comments.split('\n').map(l => l.trim()).filter(l => l.length > 0);
                    this.commentsCount = lines.length;
                    this.quantity = this.commentsCount;
                    this.calculateCharge();
                },

                calculateCharge() {
                    if (!this.currentService || !this.quantity) {
                        this.calculatedCharge = 0.00;
                        return;
                    }
                    const rate = parseFloat(this.currentService.rate);
                    this.calculatedCharge = parseFloat(((this.quantity / 1000) * rate).toFixed(4));
                }
            }
        }
    </script>
</body>
</html>
