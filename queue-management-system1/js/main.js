// Update current time
function updateTime() {
    const now = new Date();
    document.getElementById('current-time').textContent = 
        now.toLocaleTimeString() + ' - ' + now.toLocaleDateString();
}
setInterval(updateTime, 1000);
updateTime();

// Form submission
document.getElementById('customerForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const serviceType = document.getElementById('serviceType').value;
    const isPriority = document.getElementById('isPriorityCustomer').checked;
    
    fetch('api/add_customer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ service_type: serviceType, is_priority: isPriority })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('generatedQueue').textContent = data.queue_number;
            document.getElementById('queueResult').classList.remove('hidden');
            document.getElementById('customerForm').reset();
            refreshQueue();
            refreshStats();
            
            // Auto-hide success message after 5 seconds
            setTimeout(() => {
                document.getElementById('queueResult').classList.add('hidden');
            }, 5000);
        } else {
            showError(data.message || 'Failed to generate queue number');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while generating queue number');
    });
});

// Display settings form submission
const displaySettingsForm = document.getElementById('displaySettingsForm');
if (displaySettingsForm) {
    displaySettingsForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        let adVideoUrl = document.getElementById('adVideoUrl').value.trim();
        const announcementText = document.getElementById('announcementText').value.trim();
        const adVideoFileInput = document.getElementById('adVideoFile');

        try {
            if (adVideoFileInput && adVideoFileInput.files && adVideoFileInput.files.length > 0) {
                const formData = new FormData();
                formData.append('ad_video', adVideoFileInput.files[0]);

                const uploadResponse = await fetch('api/upload_ad_video.php', {
                    method: 'POST',
                    body: formData,
                });

                const uploadData = await uploadResponse.json();
                if (!uploadResponse.ok || !uploadData.success) {
                    showError(uploadData.message || 'Failed to upload local video');
                    return;
                }

                adVideoUrl = uploadData.video_path;
                document.getElementById('adVideoUrl').value = adVideoUrl;
            }

            const response = await fetch('api/update_display_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ad_video_url: adVideoUrl,
                    announcement_text: announcementText,
                })
            });

            const data = await response.json();
            if (data.success) {
                showSuccess(data.message || 'Display settings saved');
                if (adVideoFileInput) {
                    adVideoFileInput.value = '';
                }
            } else {
                showError(data.message || 'Failed to save display settings');
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Failed to save display settings');
        }
    });
}

// Refresh queue data
function refreshQueue() {
    fetch('api/get_queue.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updateQueueTable(data.customers);
                updateCounters(data.counters);
            } else {
                showError(data.message || 'Failed to load queue data');
            }
        })
        .catch(error => {
            console.error('Error loading queue:', error);
            showError('Failed to load queue data: ' + error.message);
        });
}

// Update queue table
function updateQueueTable(customers) {
    const table = document.getElementById('queueTable');
    
    if (!customers || customers.length === 0) {
        table.innerHTML = `
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2 block"></i>
                    No customers in queue
                </td>
            </tr>
        `;
        return;
    }
    
    table.innerHTML = '';
    
    customers.forEach(customer => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        
        let statusClass = '';
        let statusText = '';
        switch(customer.status) {
            case 'waiting': 
                statusClass = 'bg-yellow-100 text-yellow-800';
                statusText = 'Waiting';
                break;
            case 'serving': 
                statusClass = 'bg-blue-100 text-blue-800';
                statusText = 'Serving';
                break;
            case 'completed': 
                statusClass = 'bg-green-100 text-green-800';
                statusText = 'Completed';
                break;
            case 'cancelled': 
                statusClass = 'bg-red-100 text-red-800';
                statusText = 'Cancelled';
                break;
        }
        
        row.innerHTML = `
            <td class="px-4 py-3">
                <span class="queue-number text-lg font-bold">${customer.queue_number}</span>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    ${String(customer.service_type || '').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}
                </span>
                ${customer.status === 'waiting' && Number(customer.is_priority) === 1 ? `
                    <span class="ml-2 px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                        PRIORITY
                    </span>
                ` : ''}
            </td>
            <td class="px-4 py-3">
                <span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">
                    ${statusText}
                </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-500">
                ${new Date(customer.created_at).toLocaleTimeString()}
            </td>
            <td class="px-4 py-3">
                <div class="flex space-x-2">
                    ${customer.status === 'waiting' ? `
                        <button onclick="callCustomer(${customer.id})" 
                                class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600 transition duration-200">
                            <i class="fas fa-bullhorn mr-1"></i>Call
                        </button>
                        <button onclick="setCustomerPriority(${customer.id}, ${Number(customer.is_priority) === 1 ? 0 : 1})" 
                                class="${Number(customer.is_priority) === 1 ? 'bg-gray-500 hover:bg-gray-600' : 'bg-yellow-500 hover:bg-yellow-600'} text-white px-3 py-1 rounded text-sm transition duration-200">
                            <i class="fas ${Number(customer.is_priority) === 1 ? 'fa-star-half-alt' : 'fa-star'} mr-1"></i>${Number(customer.is_priority) === 1 ? 'Unpriority' : 'Priority'}
                        </button>
                    ` : ''}
                    ${customer.status === 'serving' ? `
                        <button onclick="completeCustomer(${customer.id})" 
                                class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition duration-200">
                            <i class="fas fa-check mr-1"></i>Complete
                        </button>
                    ` : ''}
                    ${customer.status !== 'completed' && customer.status !== 'cancelled' ? `
                        <button onclick="cancelCustomer(${customer.id})" 
                                class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600 transition duration-200">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        table.appendChild(row);
    });
}

function setCustomerPriority(customerId, isPriority) {
    fetch('api/set_customer_priority.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ customer_id: customerId, is_priority: isPriority })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshQueue();
            showSuccess(data.message || 'Priority updated');
        } else {
            showError(data.message || 'Failed to update priority');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to update priority');
    });
}

function getCounterDetailUrl(counterId) {
    return `counter.php?counter_id=${encodeURIComponent(counterId)}`;
}

// Update counters status
function updateCounters(counters) {
    const container = document.getElementById('countersStatus');
    
    if (!counters || counters.length === 0) {
        container.innerHTML = '<div class="text-center text-gray-500">No counters configured</div>';
        return;
    }
    
    container.innerHTML = '';
    
    counters.forEach(counter => {
        const counterDiv = document.createElement('div');
        counterDiv.className = `border rounded-lg p-4 ${counter.is_online ? 'bg-blue-50 border-blue-200' : 'bg-red-50 border-red-200'}`;
        
        const serviceTypesDisplay = Array.isArray(counter.service_types) 
            ? counter.service_types.map(s => s.replace('_', ' ')).join(', ') 
            : 'General';
        
        const waitingCount = counter.waiting_count || 0;
        const hasWaiting = waitingCount > 0;
        const isServing = counter.current_customer_name ? true : false;
        const counterDetailUrl = getCounterDetailUrl(counter.id);
        
        counterDiv.innerHTML = `
            <div class="flex justify-between items-center mb-2">
                <h4 class="font-bold text-lg">
                    <a href="${counterDetailUrl}" class="text-blue-700 hover:text-blue-900 hover:underline" title="Open ${counter.name} details">
                        ${counter.name}
                    </a>
                </h4>
                <span class="px-2 py-1 rounded text-xs font-semibold ${counter.is_online ? 'bg-green-200 text-green-800' : 'bg-red-200 text-red-800'}">
                    ${counter.is_online ? '● Online' : '● Offline'}
                </span>
            </div>
            <div class="text-xs text-gray-600 mb-2 font-medium">
                📋 ${serviceTypesDisplay}
            </div>
            <div class="text-sm mb-3 min-h-[40px]">
                ${isServing ? 
                    `<div class="bg-blue-100 border border-blue-300 rounded px-2 py-1">
                        <div class="text-xs text-gray-600">Now Serving:</div>
                        <div class="font-bold text-blue-800">${counter.current_queue_number || counter.current_customer_name}</div>
                    </div>` : 
                    '<div class="text-gray-500 italic">Available</div>'}
            </div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Waiting: <span class="font-bold text-blue-600">${waitingCount}</span></span>
            </div>
            <div class="flex gap-2">
                ${counter.is_online ? `
                    <a href="${counterDetailUrl}" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-semibold transition duration-200">
                        <i class="fas fa-arrow-right mr-1"></i>Open
                    </a>
                    <div class="flex-1"></div>
                    ${!isServing && hasWaiting ? `
                        <button onclick="callNextCustomer(${counter.id})" 
                                class="flex-1 bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm font-semibold transition duration-200">
                            <i class="fas fa-phone mr-1"></i>Call Next
                        </button>
                    ` : !isServing && !hasWaiting ? `
                        <button disabled 
                                class="flex-1 bg-gray-300 text-gray-500 px-3 py-2 rounded text-sm font-semibold cursor-not-allowed">
                            <i class="fas fa-hourglass-half mr-1"></i>No Queue
                        </button>
                    ` : isServing ? `
                        <button onclick="completeCurrentCustomer(${counter.id})" 
                                class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm font-semibold transition duration-200">
                            <i class="fas fa-check mr-1"></i>Complete
                        </button>
                    ` : ''}
                    <button onclick="toggleCounter(${counter.id}, false)" 
                            class="flex-1 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-sm font-semibold transition duration-200">
                        <i class="fas fa-power-off mr-1"></i>Deactivate
                    </button>
                ` : `
                    <button onclick="toggleCounter(${counter.id}, true)" 
                            class="flex-1 bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm font-semibold transition duration-200">
                        <i class="fas fa-power-off mr-1"></i>Activate Counter
                    </button>
                `}
            </div>
        `;
        container.appendChild(counterDiv);
    });
}

// Customer actions
function callCustomer(customerId) {
    fetch('api/call_customer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ customer_id: customerId })
    })
    .then(async response => {
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error(text || `HTTP ${response.status}`);
        }
        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }
        return data;
    })
    .then(data => {
        if (data.success) {
            refreshQueue();
            refreshStats();
            if (data.assignment_type === 'service_match') {
                showSuccess(`Assigned to ${data.counter_name} (matched ${data.service_type})`);
            } else {
                showSuccess(`Assigned to ${data.counter_name} (fallback routing)`);
            }
        } else {
            showError(data.message || 'Failed to call customer');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(error.message || 'Failed to call customer');
    });
}

// Call next customer for a specific counter
function callNextCustomer(counterId) {
    fetch('api/call_customer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ counter_id: counterId, customer_id: 0 })
    })
    .then(async response => {
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            throw new Error(text || `HTTP ${response.status}`);
        }
        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }
        return data;
    })
    .then(data => {
        if (data.success) {
            refreshQueue();
            refreshStats();
            showSuccess(`Next customer called to ${data.counter_name}`);
        } else {
            showError(data.message || 'Failed to call next customer');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(error.message || 'Failed to call next customer');
    });
}

// Complete current customer at counter
function completeCurrentCustomer(counterId) {
    // First get the current customer for this counter
    fetch('api/get_queue.php')
        .then(response => response.json())
        .then(queueData => {
            if (queueData.success) {
                const counter = queueData.counters.find(c => c.id == counterId);
                if (counter && counter.current_customer_id) {
                    completeCustomer(counter.current_customer_id);
                } else {
                    showError('No customer being served at this counter');
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to get counter information');
        });
}

function completeCustomer(customerId) {
    fetch('api/complete_customer.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ customer_id: customerId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshQueue();
            refreshStats();
        } else {
            showError(data.message || 'Failed to complete customer');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to complete customer');
    });
}

function cancelCustomer(customerId) {
    if (confirm('Are you sure you want to cancel this customer?')) {
        fetch('api/cancel_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_id: customerId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                refreshQueue();
                refreshStats();
            } else {
                showError(data.message || 'Failed to cancel customer');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to cancel customer');
        });
    }
}

// Refresh stats
function refreshStats() {
    fetch('api/get_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                document.getElementById('waiting-count').textContent = stats.waiting;
                document.getElementById('serving-count').textContent = stats.serving;
                document.getElementById('completed-count').textContent = stats.completed;
                document.getElementById('today-count').textContent = stats.today_total;
            } else {
                showError(data.message || 'Failed to load statistics');
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
            showError('Failed to load statistics');
        });
    
    // Also fetch alerts
    refreshAlerts();
}

// Fetch and display alerts
function refreshAlerts() {
    fetch('api/get_alerts.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.alerts && data.alerts.length > 0) {
                displayAlerts(data.alerts, data.has_critical);
            }
        })
        .catch(error => {
            console.error('Error loading alerts:', error);
        });
}

// Display alerts on admin panel
function displayAlerts(alerts, hasCritical) {
    let alertContainer = document.getElementById('alertsContainer');
    
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'alertsContainer';
        alertContainer.className = 'fixed top-20 left-4 max-w-sm z-50 space-y-3';
        document.body.appendChild(alertContainer);
    }
    
    alertContainer.innerHTML = '';
    
    alerts.forEach(alert => {
        const alertDiv = document.createElement('div');
        let bgColor = 'bg-yellow-100 border-yellow-400 text-yellow-800';
        let icon = 'fa-exclamation-triangle';
        
        if (alert.severity === 'critical') {
            bgColor = 'bg-red-100 border-red-400 text-red-800';
            icon = 'fa-exclamation-circle';
        } else if (alert.severity === 'info') {
            bgColor = 'bg-blue-100 border-blue-400 text-blue-800';
            icon = 'fa-info-circle';
        }
        
        alertDiv.className = `border-l-4 ${bgColor} p-4 rounded-lg shadow-lg`;
        alertDiv.innerHTML = `
            <div class="flex items-start">
                <i class="fas ${icon} mr-3 mt-1"></i>
                <div>
                    <p class="font-semibold">${alert.message}</p>
                    ${alert.customers ? `<p class="text-sm mt-1">Queue: ${alert.customers.map(c => c.queue_number).join(', ')}</p>` : ''}
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        alertContainer.appendChild(alertDiv);
    });
    
    // Auto-remove alerts after 10 seconds
    setTimeout(() => {
        if (alertContainer && alertContainer.children.length > 0) {
            Array.from(alertContainer.children).forEach(child => {
                child.remove();
            });
        }
    }, 10000);
}

// Show error message
function showError(message) {
    // Create or show error notification
    let errorDiv = document.getElementById('errorNotification');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'errorNotification';
        errorDiv.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        document.body.appendChild(errorDiv);
    }
    
    errorDiv.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (errorDiv.parentElement) {
            errorDiv.remove();
        }
    }, 5000);
}

function showSuccess(message) {
    let successDiv = document.getElementById('successNotification');
    if (!successDiv) {
        successDiv = document.createElement('div');
        successDiv.id = 'successNotification';
        successDiv.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
        document.body.appendChild(successDiv);
    }

    successDiv.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    setTimeout(() => {
        if (successDiv.parentElement) {
            successDiv.remove();
        }
    }, 5000);
}

// Reset Queue - Delete all customers and reset all counters
async function resetQueue() {
    const confirmed = confirm(
        '⚠️ WARNING: This will permanently delete ALL customers and clear the entire queue history.\n\nThis action CANNOT be undone. Are you sure you want to continue?'
    );
    
    if (!confirmed) return;
    
    const doubleConfirm = confirm('This is your final confirmation. Click OK to reset the entire queue.');
    if (!doubleConfirm) return;
    
    try {
        const response = await fetch('api/reset_queue.php', {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess(data.message);
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showError(data.error || 'Failed to reset queue');
        }
    } catch (error) {
        showError('Error resetting queue: ' + error.message);
    }
}

// Auto-refresh every 10 seconds
setInterval(() => {
    refreshQueue();
    refreshStats();
}, 10000);

// Toggle counter activation/deactivation
function toggleCounter(counterId, isOnline) {
    fetch('api/toggle_counter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ counter_id: counterId, is_online: isOnline })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            refreshQueue();
            showSuccess(data.message || 'Counter status updated');
        } else {
            showError(data.message || 'Failed to update counter status');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to update counter status');
    });
}

// Initial load
refreshQueue();
refreshStats();