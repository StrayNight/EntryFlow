/**
 * EntryFlow - Main Application Scripts
 */

// --- Modal Controls & Reset ---
function resetForm(formId) {
    document.getElementById(formId).reset();
    if(document.getElementById('txId')) document.getElementById('txId').value = '';
    if(document.getElementById('clId')) document.getElementById('clId').value = '';
}

function openModal(modalId) {
    if(modalId === 'transactionModal') {
        resetForm('txForm'); 
        document.querySelector('#transactionModal .modal-title').innerText = 'Add Transaction';
    }
    if(modalId === 'clientModal') {
        resetForm('clientForm'); 
        document.querySelector('#clientModal .modal-title').innerText = 'Add Client';
    }
    document.getElementById(modalId).classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
}

// --- Delete Logic ---
async function confirmDelete(type, id) {
    if (confirm(`Are you sure you want to delete this ${type}? This action cannot be undone.`)) {
        // Pass the ID directly in the URL query string
        const endpoint = type === 'transaction' ? `api/transactions.php?id=${id}` : `api/clients.php?id=${id}`;
        
        try {
            const response = await fetch(endpoint, { method: 'DELETE' });
            const result = await response.json();
            
            if (result.success || result.message) {
                window.location.reload(); 
            } else {
                alert("Error: " + (result.error || `Failed to delete ${type}`));
            }
        } catch (err) {
            alert("Error: Could not connect to the server.");
        }
    }
}

// --- Edit Populators ---
function editClient(btn) {
    const ds = btn.dataset;
    document.getElementById('clId').value = ds.id;
    document.getElementById('clName').value = ds.name;
    document.getElementById('clEmail').value = ds.email;
    document.getElementById('clPhone').value = ds.phone;
    document.getElementById('clAddress').value = ds.address;
    document.getElementById('clNotes').value = ds.notes;
    
    document.querySelector('#clientModal .modal-title').innerText = 'Edit Client';
    document.getElementById('clientModal').classList.add('active');
}

function editTransaction(btn) {
    const ds = btn.dataset;
    document.getElementById('txId').value = ds.id;
    document.getElementById('txDesc').value = ds.desc;
    document.getElementById('txInv').value = ds.inv || '';
    document.getElementById('txAmount').value = ds.amount;
    document.getElementById('txType').value = ds.type;
    document.getElementById('txCat').value = ds.cat || '';
    document.getElementById('txClient').value = ds.client || '';
    document.getElementById('txDate').value = ds.date;
    document.getElementById('txStatus').value = ds.status;
    document.getElementById('txNotes').value = ds.notes || '';

    document.querySelector('#transactionModal .modal-title').innerText = 'Edit Transaction';
    document.getElementById('transactionModal').classList.add('active');
}

// --- API Helper (Now supports POST and PUT) ---
async function apiCall(endpoint, data, method = 'POST') {
    try {
        const response = await fetch(endpoint, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await response.json();
    } catch (error) {
        console.error("API Error:", error);
        return { success: false, error: "Network error" };
    }
}

// --- Handle Form Submissions ---
document.addEventListener('DOMContentLoaded', () => {

    const txForm = document.getElementById('txForm');
    if (txForm) {
        txForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const idVal = document.getElementById('txId').value;
            const method = idVal ? 'PUT' : 'POST'; // If ID exists, it's an Update (PUT)
            
            const payload = {
                description: document.getElementById('txDesc').value,
                invoice_no: document.getElementById('txInv').value,
                amount: document.getElementById('txAmount').value,
                type: document.getElementById('txType').value,
                category_id: document.getElementById('txCat').value,
                client_id: document.getElementById('txClient').value,
                transaction_date: document.getElementById('txDate').value,
                status: document.getElementById('txStatus').value,
                notes: document.getElementById('txNotes').value
            };
            if(idVal) payload.id = idVal;

            const result = await apiCall('api/transactions.php', payload, method);
            if (result.success || result.message) {
                window.location.reload(); 
            } else {
                alert("Error: " + (result.error || "Failed to save transaction"));
            }
        });
    }

    const clientForm = document.getElementById('clientForm');
    if (clientForm) {
        clientForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const idVal = document.getElementById('clId').value;
            const method = idVal ? 'PUT' : 'POST';
            
            const payload = {
                name: document.getElementById('clName').value,
                email: document.getElementById('clEmail').value,
                phone: document.getElementById('clPhone').value,
                address: document.getElementById('clAddress').value,
                notes: document.getElementById('clNotes').value
            };
            if(idVal) payload.id = idVal;

            const result = await apiCall('api/clients.php', payload, method);
            if (result.success || result.message) {
                window.location.reload(); 
            } else {
                alert("Error: " + (result.error || "Failed to save client"));
            }
        });
    }
});