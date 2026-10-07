<?php
/**
 * ApexSMM - Enterprise SMM Panel Platform
 * Admin: Services & Catalog Management
 */

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    flash('error', 'Please authenticate to access the admin panel.');
    redirect('/admin/login.php');
}

if (!Auth::isAdmin()) {
    flash('error', 'Access denied: Administrator privileges required.');
    redirect('/user/dashboard.php');
}

// Handle Add/Edit Service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_service') {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'default';
    $rate = (float)($_POST['rate'] ?? 0);
    $min = (int)($_POST['min_quantity'] ?? 10);
    $max = (int)($_POST['max_quantity'] ?? 100000);
    $description = trim($_POST['description'] ?? '');
    $providerId = !empty($_POST['provider_id']) ? (int)$_POST['provider_id'] : null;
    $providerServiceId = trim($_POST['provider_service_id'] ?? '') ?: null;
    $refill = isset($_POST['refill']) ? 1 : 0;
    $cancel = isset($_POST['cancel']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;

    if ($name && $categoryId && $rate > 0) {
        $data = [
            'category_id'         => $categoryId,
            'name'                => $name,
            'type'                => $type,
            'rate'                => $rate,
            'min_quantity'        => $min,
            'max_quantity'        => $max,
            'description'         => $description,
            'provider_id'         => $providerId,
            'provider_service_id' => $providerServiceId,
            'refill'              => $refill,
            'cancel'              => $cancel,
            'status'              => $status,
        ];

        if ($serviceId) {
            Database::update('services', $data, 'id = :id', [':id' => $serviceId]);
            flash('success', "Service #{$serviceId} updated successfully.");
        } else {
            $newId = Database::insert('services', $data);
            flash('success', "New Service #{$newId} created successfully.");
        }
    }
    redirect('/admin/services/index.php');
}

$categories = Database::fetchAll("SELECT * FROM categories ORDER BY sort_order ASC");
$providers = Database::fetchAll("SELECT * FROM providers ORDER BY id ASC");
$services = Database::fetchAll("SELECT s.*, c.name as category_name, p.name as provider_name FROM services s JOIN categories c ON c.id = s.category_id LEFT JOIN providers p ON p.id = s.provider_id ORDER BY s.id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Control - Admin ApexSMM</title>
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="theme-light panel-layout" x-data="{ sidebarOpen: false, modalOpen: false, editService: {} }">

    <aside class="sidebar" :class="{ 'open': sidebarOpen }">
        <div class="sidebar-header">
            <a href="<?= url('admin/dashboard.php') ?>" class="brand-logo">
                <div class="logo-icon" style="background: linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <span>Apex<span>ADMIN</span></span>
            </a>
            <button class="btn-close-sidebar" @click="sidebarOpen = false">&times;</button>
        </div>

        <nav class="sidebar-nav">
            <a href="<?= url('admin/dashboard.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="<?= url('admin/orders/index.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                <span>All Orders</span>
            </a>
            <a href="<?= url('admin/services/index.php') ?>" class="nav-item active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span>Services</span>
            </a>
            <a href="<?= url('admin/users/index.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                <span>Users</span>
            </a>
            <a href="<?= url('admin/providers/index.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                <span>Providers</span>
            </a>
            <a href="<?= url('admin/settings/index.php') ?>" class="nav-item">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                <span>System Settings</span>
            </a>
            <div class="sidebar-divider"></div>
            <a href="<?= url('user/dashboard.php') ?>" class="nav-item">
                <span>&larr; Client Portal</span>
            </a>
        </nav>
    </aside>

    <main class="main-panel">
        <header class="topbar">
            <button class="btn-hamburger" @click="sidebarOpen = true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
            <div class="topbar-title">Services & Pricing Management</div>
            <div class="topbar-actions">
                <button class="btn btn-primary btn-sm" @click="editService = { id: 0, category_id: <?= $categories[0]['id'] ?? 1 ?>, type: 'default', rate: '1.0000', min_quantity: 50, max_quantity: 10000, status: 1, refill: 1, cancel: 0 }; modalOpen = true;">
                    + Add New Service
                </button>
            </div>
        </header>

        <div class="panel-body">
            <?= render_flashes() ?>

            <div class="card">
                <div class="card-body" style="padding: 0;">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Service Name</th>
                                    <th>Category</th>
                                    <th>Rate / 1K</th>
                                    <th>Min / Max</th>
                                    <th>Provider Mapping</th>
                                    <th>Status</th>
                                    <th style="text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $srv): ?>
                                    <tr>
                                        <td class="cell-id">#<?= $srv['id'] ?></td>
                                        <td style="font-weight: 500; font-size: 13px;"><?= htmlspecialchars($srv['name']) ?></td>
                                        <td><span class="badge badge-muted"><?= htmlspecialchars($srv['category_name']) ?></span></td>
                                        <td class="cell-rate"><?= rate_per_k($srv['rate']) ?></td>
                                        <td><?= number_format($srv['min_quantity']) ?> / <?= number_short($srv['max_quantity']) ?></td>
                                        <td style="font-size: 12px; color: var(--text-muted);">
                                            <?= $srv['provider_name'] ? htmlspecialchars($srv['provider_name']) . " (#{$srv['provider_service_id']})" : 'Manual' ?>
                                        </td>
                                        <td><?= $srv['status'] ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-fail">Disabled</span>' ?></td>
                                        <td style="text-align: right;">
                                            <button class="btn btn-xs btn-secondary" @click="editService = <?= htmlspecialchars(json_encode($srv), ENT_QUOTES) ?>; modalOpen = true;">Edit</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for Adding / Editing Service -->
    <div class="modal-backdrop" x-show="modalOpen" style="display: none;" @keydown.escape.window="modalOpen = false">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 x-text="editService.id ? 'Edit Service #' + editService.id : 'Add New Service'"></h3>
                <button type="button" class="btn-close-modal" @click="modalOpen = false">&times;</button>
            </div>
            <form method="POST" action="<?= url('admin/services/index.php') ?>">
                <input type="hidden" name="action" value="save_service">
                <input type="hidden" name="service_id" :value="editService.id">

                <div class="modal-body">
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" class="form-control" x-model="editService.category_id" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Service Name</label>
                        <input type="text" name="name" class="form-control" x-model="editService.name" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Rate / 1,000 (USD)</label>
                            <input type="number" step="0.0001" name="rate" class="form-control" x-model="editService.rate" required>
                        </div>
                        <div class="form-group">
                            <label>Service Type</label>
                            <select name="type" class="form-control" x-model="editService.type">
                                <option value="default">Default</option>
                                <option value="custom_comments">Custom Comments</option>
                                <option value="package">Package</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Min Quantity</label>
                            <input type="number" name="min_quantity" class="form-control" x-model="editService.min_quantity" required>
                        </div>
                        <div class="form-group">
                            <label>Max Quantity</label>
                            <input type="number" name="max_quantity" class="form-control" x-model="editService.max_quantity" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="form-group">
                            <label>Provider</label>
                            <select name="provider_id" class="form-control" x-model="editService.provider_id">
                                <option value="">None (Manual)</option>
                                <?php foreach ($providers as $prov): ?>
                                    <option value="<?= $prov['id'] ?>"><?= htmlspecialchars($prov['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Provider Service ID</label>
                            <input type="text" name="provider_service_id" class="form-control" x-model="editService.provider_service_id" placeholder="Remote ID">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" class="form-control" x-model="editService.description"></textarea>
                    </div>

                    <div style="display: flex; gap: 20px; margin-top: 10px;">
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="refill" value="1" :checked="editService.refill == 1"> Auto Refill Button
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="cancel" value="1" :checked="editService.cancel == 1"> Cancel Button
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer;">
                            <input type="checkbox" name="status" value="1" :checked="editService.status == 1"> Active / Visible
                        </label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" @click="modalOpen = false">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Service</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
