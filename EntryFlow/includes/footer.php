<?php
// includes/footer.php
$modalDb = getDB();
$modalUid = (int)($_SESSION['user_id'] ?? 0);
$clientsList = $modalDb->query("SELECT id, name FROM clients WHERE user_id=$modalUid ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$categoriesList = $modalDb->query("SELECT id, name, type FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>
        </div> </main> <div id="transactionModal" class="modal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <div class="modal-title">Add Transaction</div>
                <button class="close-btn" onclick="closeModal('transactionModal')">&times;</button>
            </div>
            <form id="txForm">
                <input type="hidden" id="txId" value="">
                
                <div class="form-group" style="display: flex; gap: 10px;">
                    <div style="flex: 2;">
                        <label>Description *</label>
                        <input type="text" id="txDesc" class="sb" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Invoice No.</label>
                        <input type="text" id="txInv" class="sb" placeholder="Optional">
                    </div>
                </div>

                <div class="form-group" style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label>Amount (₱) *</label>
                        <input type="number" id="txAmount" class="sb" step="0.01" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Type *</label>
                        <select id="txType" class="sb" required>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label>Category</label>
                        <select id="txCat" class="sb">
                            <option value="">-- None --</option>
                            <?php foreach($categoriesList as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?> (<?= ucfirst($cat['type']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label>Client</label>
                        <select id="txClient" class="sb">
                            <option value="">-- None --</option>
                            <?php foreach($clientsList as $cli): ?>
                                <option value="<?= $cli['id'] ?>"><?= htmlspecialchars($cli['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label>Date *</label>
                        <input type="date" id="txDate" class="sb" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div style="flex: 1;">
                        <label>Status *</label>
                        <select id="txStatus" class="sb" required>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea id="txNotes" class="sb" rows="2" style="width: 100%; resize: vertical;"></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Save Transaction</button>
            </form>
        </div>
    </div>

    <div id="clientModal" class="modal">
        <div class="modal-content">
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
</body>
</html>