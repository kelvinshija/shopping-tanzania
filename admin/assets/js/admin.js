// Admin Panel JavaScript

// Confirm actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Handle AJAX status updates
async function updateOrderStatus(orderId, newStatus) {
    try {
        const response = await fetch('/ecommerce-platform/api/admin/update-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                status: newStatus
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Order status updated successfully', 'success');
        } else {
            showNotification(data.message || 'Failed to update order status', 'error');
        }
    } catch (error) {
        showNotification('An error occurred', 'error');
    }
}

// Show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const csvData = [];
    
    rows.forEach(row => {
        const rowData = [];
        row.querySelectorAll('td, th').forEach(cell => {
            rowData.push(cell.innerText);
        });
        csvData.push(rowData.join(','));
    });
    
    const csvContent = csvData.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Add confirm dialogs to delete buttons
    document.querySelectorAll('.btn-delete, .btn-danger[onclick*="confirm"]').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);