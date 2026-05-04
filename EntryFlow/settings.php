<?php
session_start();
require_once 'config.php';
requireLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

// Fetch current admin profile data
$userQuery = $db->query("SELECT * FROM admins WHERE id = $userId");
$user = $userQuery->fetch_assoc();

include 'includes/header.php'; 
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    
    <div style="background: var(--surface-1); padding: 24px; border-radius: 12px; border: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 20px; color: var(--primary-light);">User Profile</h3>
        <form id="profileForm">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 12px; color: var(--text-muted);">Full Name *</label>
                <input type="text" id="p_name" class="sb" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required style="width: 100%;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 12px; color: var(--text-muted);">Email Address *</label>
                <input type="email" id="p_email" class="sb" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required style="width: 100%;">
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: var(--text-muted);">Phone Number</label>
                    <input type="text" id="p_phone" class="sb" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" style="width: 100%;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: var(--text-muted);">Physical Address</label>
                    <input type="text" id="p_address" class="sb" value="<?= htmlspecialchars($user['address'] ?? '') ?>" style="width: 100%;">
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">
            <h4 style="margin-bottom: 15px; color: var(--text-main); font-size: 14px;">Change Password</h4>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 12px; color: var(--text-muted);">Current Password (Required only if changing password)</label>
                <input type="password" id="p_current_pass" class="sb" placeholder="••••••••" style="width: 100%;">
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 5px; font-size: 12px; color: var(--text-muted);">New Password</label>
                <input type="password" id="p_new_pass" class="sb" placeholder="••••••••" style="width: 100%;">
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Update Profile</button>
        </form>
    </div>

    <div style="background: var(--surface-1); padding: 24px; border-radius: 12px; border: 1px solid var(--border-color);">
        <h3 style="margin-bottom: 20px; color: var(--primary-light);">Business Information</h3>
        <form id="businessForm">
            <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-size: 12px; color: var(--text-muted);">Business Name *</label>
                <input type="text" id="b_name" class="sb" value="<?= htmlspecialchars($user['business_name'] ?? '') ?>" required style="width: 100%;">
            </div>
            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: var(--text-muted);">Business Type</label>
                    <input type="text" id="b_type" class="sb" value="<?= htmlspecialchars($user['business_type'] ?? '') ?>" placeholder="e.g. Freelance, Retail" style="width: 100%;">
                </div>
                <div style="flex: 1;">
                    <label style="display: block; margin-bottom: 5px; font-size: 12px; color: var(--text-muted);">Default Currency</label>
                    <select id="b_currency" class="sb" style="width: 100%;">
                        <option value="PHP" <?= ($user['currency'] === 'PHP') ? 'selected' : '' ?>>PHP - Philippine Peso (₱)</option>
                        <option value="USD" <?= ($user['currency'] === 'USD') ? 'selected' : '' ?>>USD - US Dollar ($)</option>
                        <option value="EUR" <?= ($user['currency'] === 'EUR') ? 'selected' : '' ?>>EUR - Euro (€)</option>
                        <option value="GBP" <?= ($user['currency'] === 'GBP') ? 'selected' : '' ?>>GBP - British Pound (£)</option>
                        <option value="JPY" <?= ($user['currency'] === 'JPY') ? 'selected' : '' ?>>JPY - Japanese Yen (¥)</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 10px;">Save Business Settings</button>
        </form>
    </div>
</div>

<script>
// Logic to handle form submissions via your API
document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
        name: document.getElementById('p_name').value,
        email: document.getElementById('p_email').value,
        phone: document.getElementById('p_phone').value,
        address: document.getElementById('p_address').value,
        current_password: document.getElementById('p_current_pass').value,
        new_password: document.getElementById('p_new_pass').value
    };

    try {
        const res = await fetch('api/settings.php?action=profile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            alert('Profile updated successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (err) {
        alert('Network error while updating profile.');
    }
});

document.getElementById('businessForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {
        business_name: document.getElementById('b_name').value,
        business_type: document.getElementById('b_type').value,
        currency: document.getElementById('b_currency').value
    };

    try {
        const res = await fetch('api/settings.php?action=settings', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();
        if (result.success) {
            alert('Business settings updated successfully!');
            window.location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (err) {
        alert('Network error while updating business settings.');
    }
});
</script>

<?php include 'includes/footer.php'; ?>