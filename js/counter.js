const counterId = Number(window.COUNTER_ID || 0);
let currentCounter = null;
let currentQueue = [];

function formatServiceName(service) {
    return String(service || '').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
}

function showMessage(message, type = 'error') {
    const id = type === 'error' ? 'counterErrorNotification' : 'counterSuccessNotification';
    const className = type === 'error'
        ? 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50'
        : 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
    const icon = type === 'error' ? 'fa-exclamation-triangle' : 'fa-check-circle';

    let box = document.getElementById(id);
    if (!box) {
        box = document.createElement('div');
        box.id = id;
        box.className = className;
        document.body.appendChild(box);
    }

    box.innerHTML = `
        <div class="flex items-center">
            <i class="fas ${icon} mr-2"></i>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;

    setTimeout(() => {
        if (box && box.parentElement) {
            box.remove();
        }
    }, 5000);
}

function showError(message) {
    showMessage(message, 'error');
}

function showSuccess(message) {
    showMessage(message, 'success');
}

function updateHeader(counter) {
    document.getElementById('counterTitle').textContent = counter.name || `Counter ${counterId}`;
    const services = Array.isArray(counter.service_types) && counter.service_types.length > 0
        ? counter.service_types.map(formatServiceName).join(', ')
        : 'No service type configured';
    document.getElementById('counterServices').textContent = `Services: ${services}`;
}

function updateStats(counter) {
    const statusLabel = counter.is_online ? 'Online' : 'Offline';
    document.getElementById('counterStatus').textContent = statusLabel;
    document.getElementById('counterStatus').className = `text-2xl font-bold ${counter.is_online ? 'text-green-600' : 'text-red-600'}`;

    const nowServing = counter.current_queue_number || '-';
    document.getElementById('nowServing').textContent = nowServing;
    document.getElementById('waitingCount').textContent = counter.waiting_count || 0;

    const callNextBtn = document.getElementById('callNextBtn');
    const completeBtn = document.getElementById('completeBtn');
    const toggleBtn = document.getElementById('toggleBtn');

    const hasCurrent = !!counter.current_customer_id;
    const hasWaiting = Number(counter.waiting_count || 0) > 0;

    callNextBtn.disabled = !counter.is_online || hasCurrent || !hasWaiting;
    callNextBtn.className = callNextBtn.disabled
        ? 'bg-gray-300 text-gray-500 px-4 py-2 rounded-lg font-semibold cursor-not-allowed'
        : 'bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-semibold transition';

    completeBtn.disabled = !counter.is_online || !hasCurrent;
    completeBtn.className = completeBtn.disabled
        ? 'bg-gray-300 text-gray-500 px-4 py-2 rounded-lg font-semibold cursor-not-allowed'
        : 'bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold transition';

    if (counter.is_online) {
        toggleBtn.textContent = ' Deactivate';
        toggleBtn.prepend(Object.assign(document.createElement('i'), { className: 'fas fa-power-off mr-2' }));
        toggleBtn.className = 'bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold transition';
    } else {
        toggleBtn.textContent = ' Activate';
        toggleBtn.prepend(Object.assign(document.createElement('i'), { className: 'fas fa-power-off mr-2' }));
        toggleBtn.className = 'bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-semibold transition';
    }
}

function updateQueueTable(customers, counter) {
    const table = document.getElementById('counterQueueTable');
    const serviceTypes = Array.isArray(counter.service_types) ? counter.service_types : [];

    const rows = (customers || []).filter(customer => {
        const byService = serviceTypes.includes(customer.service_type) && customer.status === 'waiting';
        const isCurrent = Number(counter.current_customer_id || 0) === Number(customer.id);
        return byService || isCurrent;
    });

    if (rows.length === 0) {
        table.innerHTML = `
            <tr>
                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2 block"></i>
                    No queue data for this counter
                </td>
            </tr>
        `;
        return;
    }

    table.innerHTML = '';

    rows.forEach(customer => {
        let statusClass = 'bg-yellow-100 text-yellow-800';
        let statusText = 'Waiting';
        if (customer.status === 'serving') {
            statusClass = 'bg-blue-100 text-blue-800';
            statusText = 'Serving';
        }

        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        row.innerHTML = `
            <td class="px-4 py-3"><span class="queue-number text-lg font-bold">${customer.queue_number}</span></td>
            <td class="px-4 py-3">${formatServiceName(customer.service_type)}</td>
            <td class="px-4 py-3"><span class="px-3 py-1 rounded-full text-sm font-medium ${statusClass}">${statusText}</span></td>
            <td class="px-4 py-3 text-sm text-gray-500">${new Date(customer.created_at).toLocaleTimeString()}</td>
        `;
        table.appendChild(row);
    });
}

async function loadCounterData() {
    try {
        const response = await fetch('api/get_queue.php');
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to load counter data');
        }

        const counter = (data.counters || []).find(c => Number(c.id) === counterId);
        if (!counter) {
            throw new Error(`Counter ${counterId} not found`);
        }

        currentCounter = counter;
        currentQueue = data.customers || [];

        updateHeader(counter);
        updateStats(counter);
        updateQueueTable(currentQueue, counter);
    } catch (error) {
        console.error(error);
        showError(error.message || 'Failed to load data');
    }
}

async function callNextCustomer() {
    if (!currentCounter) return;

    try {
        const response = await fetch('api/call_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ counter_id: counterId, customer_id: 0 })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to call next customer');
        }

        showSuccess(data.message || 'Next customer called');
        loadCounterData();
    } catch (error) {
        console.error(error);
        showError(error.message || 'Failed to call next customer');
    }
}

async function completeCurrentCustomer() {
    if (!currentCounter || !currentCounter.current_customer_id) {
        showError('No customer is currently serving at this counter');
        return;
    }

    try {
        const response = await fetch('api/complete_customer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ customer_id: currentCounter.current_customer_id })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to complete customer');
        }

        showSuccess(data.message || 'Customer completed');
        loadCounterData();
    } catch (error) {
        console.error(error);
        showError(error.message || 'Failed to complete customer');
    }
}

async function toggleCounter() {
    if (!currentCounter) return;

    try {
        const nextState = !Boolean(currentCounter.is_online);
        const response = await fetch('api/toggle_counter.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ counter_id: counterId, is_online: nextState })
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Failed to update counter status');
        }

        showSuccess(data.message || 'Counter status updated');
        loadCounterData();
    } catch (error) {
        console.error(error);
        showError(error.message || 'Failed to update counter status');
    }
}

document.getElementById('refreshBtn').addEventListener('click', loadCounterData);
document.getElementById('callNextBtn').addEventListener('click', callNextCustomer);
document.getElementById('completeBtn').addEventListener('click', completeCurrentCustomer);
document.getElementById('toggleBtn').addEventListener('click', toggleCounter);

loadCounterData();
setInterval(loadCounterData, 10000);
