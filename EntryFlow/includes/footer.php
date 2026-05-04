<?php
// includes/footer.php
$modalDb = getDB();
$modalUid = (int)($_SESSION['user_id'] ?? 0);
$clientsList = $modalDb->query("SELECT id, name FROM clients WHERE user_id=$modalUid ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$categoriesList = $modalDb->query("SELECT id, name, type FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
    </div> 
</main> 

<div id="transactionModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <div class="modal-title" id="txModalTitle">Add Transaction</div>
            <button class="close-btn" onclick="closeModal('transactionModal')">&times;</button>
        </div>
        <form id="txForm">
            <input type="hidden" id="txId" value="">
            
            <input type="hidden" id="txType" value="income">
            
            <div class="form-group" style="display: flex; gap: 10px;">
                <div style="flex: 2;">
                    <label id="lblDesc">Description *</label>
                    <input type="text" id="txDesc" class="sb" required>
                </div>
                <div style="flex: 1;" id="divInv">
                    <label>Invoice No.</label>
                    <input type="text" id="txInv" class="sb" placeholder="Optional">
                </div>
            </div>

            <div class="form-group" style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Amount (<?= $sym ?>) *</label>
                    <input type="number" id="txAmount" class="sb" step="0.01" required>
                </div>
                <div style="flex: 1;">
                    <label>Date *</label>
                    <input type="date" id="txDate" class="sb" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-group" style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label id="lblClient">Client</label>
                    <select id="txClient" class="sb">
                        <option value="">-- None --</option>
                        <?php foreach($clientsList as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1;">
                    <label>Category</label>
                    <select id="txCat" class="sb">
                        <option value="">-- None --</option>
                        <?php foreach($categoriesList as $cat): ?>
                            <option value="<?= $cat['id'] ?>" class="cat-opt cat-<?= $cat['type'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select id="txStatus" class="sb">
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="overdue">Overdue</option>
                </select>
            </div>

            <div class="form-group">
                <label>Notes</label>
                <textarea id="txNotes" class="sb" rows="2" style="width: 100%;"></textarea>
            </div>

            <button type="submit" class="btn btn-primary" id="txSubmitBtn" style="width: 100%; justify-content: center;">Save</button>
        </form>
    </div>
</div>

<div id="clientModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <div class="modal-title">Add Client</div>
            <button class="close-btn" onclick="closeModal('clientModal')">&times;</button>
        </div>
        <form id="clientForm">
            <input type="hidden" id="clId" value="">
            <div class="form-group">
                <label>Client Name *</label>
                <input type="text" id="clName" class="sb" required>
            </div>
            <div class="form-group" style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Email Address</label>
                    <input type="email" id="clEmail" class="sb">
                </div>
                <div style="flex: 1;">
                    <label>Phone Number</label>
                    <input type="text" id="clPhone" class="sb">
                </div>
            </div>
            <div class="form-group">
                <label>Physical Address</label>
                <input type="text" id="clAddress" class="sb">
            </div>
            <div class="form-group">
                <label>Notes / Details</label>
                <textarea id="clNotes" class="sb" rows="2" style="width: 100%; resize: vertical;"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Save Client</button>
        </form>
    </div>
</div>

<script src="assets/js/app.js"></script>

<script>
// --- THE "ILLUSION" ENGINE ---
// This shapes the modal based on which button was clicked
function openDynamicModal(type) {
    if (typeof resetForm === "function") resetForm('txForm'); 
    
    // Lock the hidden type input
    document.getElementById('txType').value = type;
    
    // Grab the UI elements
    const title = document.getElementById('txModalTitle');
    const lblDesc = document.getElementById('lblDesc');
    const lblClient = document.getElementById('lblClient');
    const divInv = document.getElementById('divInv');
    const submitBtn = document.getElementById('txSubmitBtn');

    if (type === 'income') {
        title.innerText = 'Log a Sale';
        lblDesc.innerText = 'Product / Service Rendered *';
        lblClient.innerText = 'Customer';
        divInv.style.display = 'block'; // Show Invoice field
        submitBtn.innerText = 'Save Sale';
    } else {
        title.innerText = 'Log an Expense';
        lblDesc.innerText = 'Expense Description *';
        lblClient.innerText = 'Payee / Vendor';
        divInv.style.display = 'none'; // Hide Invoice field for expenses
        submitBtn.innerText = 'Save Expense';
    }

    // BONUS: Hide categories that don't match the selected type!
    document.querySelectorAll('.cat-opt').forEach(opt => {
        if (opt.classList.contains('cat-' + type)) {
            opt.style.display = 'block';
        } else {
            opt.style.display = 'none';
        }
    });

    document.getElementById('transactionModal').classList.add('active');
}

// Override the generic edit button so it triggers our beautiful illusion
window.editTransaction = function(btn) {
    const type = btn.getAttribute('data-type');
    
    // Call the illusion logic to set up the form
    openDynamicModal(type); 
    
    // Populate the existing data
    document.getElementById('txId').value = btn.getAttribute('data-id');
    document.getElementById('txDesc').value = btn.getAttribute('data-desc');
    document.getElementById('txInv').value = btn.getAttribute('data-inv');
    document.getElementById('txAmount').value = btn.getAttribute('data-amount');
    document.getElementById('txCat').value = btn.getAttribute('data-cat');
    document.getElementById('txClient').value = btn.getAttribute('data-client');
    document.getElementById('txDate').value = btn.getAttribute('data-date');
    document.getElementById('txStatus').value = btn.getAttribute('data-status');
    document.getElementById('txNotes').value = btn.getAttribute('data-notes');
};
</script>
</body>
</html>