<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Queue Number</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --wd-blue-900: #0b2f6b;
            --wd-blue-800: #11408a;
            --wd-blue-700: #1d56b3;
            --wd-blue-100: #dbeafe;
            --wd-sky-100: #e0f2fe;
            --wd-page: #f3f8ff;
        }

        body {
            background: var(--wd-page);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .gradient-bg {
            background: linear-gradient(135deg, var(--wd-blue-900) 0%, var(--wd-blue-700) 100%);
        }

        .wd-btn-primary {
            background: linear-gradient(135deg, var(--wd-blue-800), var(--wd-blue-700));
            transition: filter 0.2s ease;
        }
        .wd-btn-primary:hover { filter: brightness(0.92); }

        .service-card {
            border: 2px solid #d8e8ff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            cursor: pointer;
        }
        .service-card:hover {
            border-color: var(--wd-blue-700);
            box-shadow: 0 6px 20px rgba(17, 64, 138, 0.15);
            transform: translateY(-3px);
        }
        .service-card.selected {
            border-color: var(--wd-blue-700);
            background: #e8f0ff;
            box-shadow: 0 0 0 3px rgba(29, 86, 179, 0.25);
        }

        .priority-card {
            border: 2px solid #fecaca;
            cursor: pointer;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        }
        .priority-card:hover {
            border-color: #f87171;
            box-shadow: 0 4px 14px rgba(239, 68, 68, 0.15);
        }
        .priority-card.active {
            border-color: #ef4444;
            background: #fff1f1;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.2);
        }

        .ticket {
            background: white;
            border: 3px dashed #7cb8df;
            border-radius: 16px;
            position: relative;
        }
        .ticket::before,
        .ticket::after {
            content: '';
            position: absolute;
            width: 28px;
            height: 28px;
            background: var(--wd-page);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
        }
        .ticket::before { left: -14px; }
        .ticket::after  { right: -14px; }

        .queue-display {
            font-family: 'Courier New', Consolas, monospace;
            letter-spacing: 4px;
            font-size: clamp(3.25rem, 12vw, 5.5rem);
        }

        .pulse-ring {
            animation: pulseRing 1.8s ease-out infinite;
        }
        @keyframes pulseRing {
            0%   { transform: scale(0.8); opacity: 0.8; }
            70%  { transform: scale(1.3); opacity: 0;   }
            100% { transform: scale(0.8); opacity: 0;   }
        }

        #waitInfo {
            background: linear-gradient(135deg, #eff6ff, #e0f2fe);
            border: 1px solid #bfdbfe;
        }

        .page-shell {
            width: min(100%, 42rem);
        }

        .result-stats {
            grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
        }

        .result-divider {
            width: 1px;
            background: #bfdbfe;
        }

        @media (max-width: 640px) {
            body {
                overflow-x: hidden;
            }

            .service-card:hover,
            .priority-card:hover {
                transform: none;
            }

            .ticket {
                max-width: 100%;
                padding: 1.5rem 1.25rem;
            }

            .ticket::before,
            .ticket::after {
                width: 22px;
                height: 22px;
            }

            .ticket::before { left: -11px; }
            .ticket::after  { right: -11px; }

            .queue-display {
                letter-spacing: 2px;
            }

            .result-stats {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .result-divider {
                width: 100%;
                height: 1px;
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="page-shell mx-auto px-4 py-4 sm:py-5 flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
            <div class="min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold leading-tight"><i class="fas fa-ticket-alt mr-2"></i>Queue Number</h1>
                <p class="text-sm opacity-80 mt-0.5">Get your ticket to be served</p>
            </div>
            <a href="index.php"
               class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2 w-full sm:w-auto">
                <i class="fas fa-tachometer-alt"></i>
                <span class="hidden sm:inline">Dashboard</span>
                <span class="sm:hidden">Back to Dashboard</span>
            </a>
        </div>
    </header>

    <main class="page-shell mx-auto px-4 py-6 sm:py-10">

        <!-- Step 1 — Choose service -->
        <div id="stepForm">
            <p class="text-center text-gray-500 text-xs sm:text-sm mb-6 uppercase tracking-widest font-semibold">
                Select a service to get started
            </p>

            <!-- Service cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6" id="serviceCards">
                <div class="service-card bg-white rounded-xl p-5 sm:p-6 text-center"
                     data-value="bills" onclick="selectService(this)">
                    <div class="w-14 h-14 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                        <i class="fas fa-file-invoice-dollar text-2xl text-blue-600"></i>
                    </div>
                    <div class="font-bold text-gray-800 text-lg">Bills</div>
                    <div class="text-gray-500 text-xs mt-1">Payment &amp; billing</div>
                </div>

                 <div class="service-card bg-white rounded-xl p-5 sm:p-6 text-center"
                     data-value="complaints" onclick="selectService(this)">
                    <div class="w-14 h-14 mx-auto mb-3 bg-orange-50 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-circle text-2xl text-orange-500"></i>
                    </div>
                    <div class="font-bold text-gray-800 text-lg">Complaints</div>
                    <div class="text-gray-500 text-xs mt-1">Report &amp; escalate</div>
                </div>

                 <div class="service-card bg-white rounded-xl p-5 sm:p-6 text-center"
                     data-value="customer_service" onclick="selectService(this)">
                    <div class="w-14 h-14 mx-auto mb-3 bg-green-50 rounded-full flex items-center justify-center">
                        <i class="fas fa-headset text-2xl text-green-600"></i>
                    </div>
                    <div class="font-bold text-gray-800 text-lg">Customer Service</div>
                    <div class="text-gray-500 text-xs mt-1">General inquiries</div>
                </div>
            </div>

            <!-- Priority toggle -->
            <input type="checkbox" id="isPriority" class="sr-only">
            <div id="priorityCard"
                 class="priority-card bg-white rounded-xl p-4 mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between shadow-sm select-none"
                 onclick="togglePriority()">
                <div class="flex items-center gap-3 min-w-0">
                    <div id="priorityIcon" class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0 transition-colors">
                        <i class="fas fa-star text-red-400 text-lg"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-gray-800 text-sm">Priority Lane</div>
                        <div class="text-gray-500 text-xs">For senior citizens, PWD &amp; pregnant</div>
                    </div>
                </div>
                <!-- Custom visual toggle -->
                <div id="priorityToggle"
                     class="w-12 h-7 rounded-full flex items-center px-1 transition-colors duration-200 bg-gray-200 self-end sm:self-auto">
                    <div id="priorityThumb"
                         class="w-5 h-5 bg-white rounded-full shadow transition-transform duration-200 translate-x-0"></div>
                </div>
            </div>

            <!-- Estimated wait info -->
            <div id="waitInfo" class="rounded-xl p-4 mb-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center">
                <i class="fas fa-clock text-blue-500 text-xl flex-shrink-0"></i>
                <div class="min-w-0">
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Estimated Wait</div>
                    <div id="waitText" class="text-gray-700 font-medium text-sm">Fetching wait time…</div>
                </div>
                <div class="w-full sm:w-auto sm:ml-auto text-left sm:text-right">
                    <div class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Waiting</div>
                    <div id="waitCount" class="text-2xl font-bold text-blue-700">—</div>
                </div>
            </div>

            <!-- Generate button -->
            <button id="generateBtn" onclick="generateQueue()"
                    class="wd-btn-primary w-full text-white py-4 rounded-xl font-bold text-base sm:text-lg shadow-md
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-ticket-alt mr-2"></i>Generate Queue Number
            </button>

            <!-- Error message -->
            <div id="errorMsg" class="hidden mt-4 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
                <i class="fas fa-exclamation-triangle flex-shrink-0"></i>
                <span id="errorText"></span>
            </div>
        </div>

        <!-- Step 2 — Ticket result -->
        <div id="stepResult" class="hidden text-center">

            <div class="relative inline-block mb-6">
                <span class="pulse-ring absolute inset-0 rounded-full border-4 border-green-400 opacity-75"></span>
                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto">
                    <i class="fas fa-check text-white text-2xl sm:text-3xl"></i>
                </div>
            </div>

            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1">Your queue number is ready!</h2>
            <p class="text-gray-500 mb-8 text-sm">Please wait. You will be called when it's your turn.</p>

            <!-- Ticket -->
            <div class="ticket mx-auto w-full max-w-xs sm:max-w-sm py-8 px-6 mb-8 shadow-lg">
                <div class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-1">Queue Number</div>
                <div id="resultQueue" class="queue-display font-black text-blue-800 leading-none mb-4">—</div>
                <div id="resultService" class="text-sm font-semibold text-gray-600 mb-1">—</div>
                <div id="resultPriority" class="hidden inline-flex items-center gap-1 bg-red-100 text-red-700 text-xs font-bold px-3 py-1 rounded-full mb-1">
                    <i class="fas fa-star text-xs"></i> Priority
                </div>
                <hr class="border-dashed border-gray-200 my-4">
                <div class="text-xs text-gray-400" id="resultTime"></div>
            </div>

            <!-- Wait info after generation -->
            <div class="result-stats grid bg-blue-50 border border-blue-100 rounded-xl p-4 mb-8 text-center items-center">
                <div>
                    <div class="text-2xl font-bold text-blue-700" id="resultWaiting">—</div>
                    <div class="text-xs text-gray-500 mt-1">People Waiting</div>
                </div>
                <div class="result-divider justify-self-center"></div>
                <div>
                    <div class="text-2xl font-bold text-blue-700" id="resultEstWait">—</div>
                    <div class="text-xs text-gray-500 mt-1">Est. Wait (min)</div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <button onclick="resetForm()"
                        class="wd-btn-primary w-full sm:w-auto text-white px-8 py-3 rounded-xl font-semibold shadow-md">
                    <i class="fas fa-plus mr-2"></i>New Queue Number
                </button>
                <a href="display.php"
                   class="w-full sm:w-auto bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-8 py-3 rounded-xl font-semibold transition text-center">
                    <i class="fas fa-tv mr-2"></i>Watch Display
                </a>
            </div>
        </div>

    </main>

    <script>
        let selectedService = '';
        let priorityActive = false;

        function togglePriority() {
            priorityActive = !priorityActive;
            document.getElementById('isPriority').checked = priorityActive;
            const card   = document.getElementById('priorityCard');
            const toggle = document.getElementById('priorityToggle');
            const thumb  = document.getElementById('priorityThumb');
            const icon   = document.getElementById('priorityIcon');
            if (priorityActive) {
                card.classList.add('active');
                toggle.classList.replace('bg-gray-200', 'bg-red-500');
                thumb.classList.replace('translate-x-0', 'translate-x-5');
                icon.classList.replace('bg-red-50', 'bg-red-500');
                icon.querySelector('i').classList.replace('text-red-400', 'text-white');
            } else {
                card.classList.remove('active');
                toggle.classList.replace('bg-red-500', 'bg-gray-200');
                thumb.classList.replace('translate-x-5', 'translate-x-0');
                icon.classList.replace('bg-red-500', 'bg-red-50');
                icon.querySelector('i').classList.replace('text-white', 'text-red-400');
            }
        }

        function selectService(card) {
            document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedService = card.dataset.value;
        }

        function showError(msg) {
            document.getElementById('errorText').textContent = msg;
            document.getElementById('errorMsg').classList.remove('hidden');
        }

        function hideError() {
            document.getElementById('errorMsg').classList.add('hidden');
        }

        async function generateQueue() {
            hideError();

            if (!selectedService) {
                showError('Please select a service type first.');
                return;
            }

            const btn = document.getElementById('generateBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Generating…';

            const isPriority = document.getElementById('isPriority').checked;

            try {
                const res  = await fetch('api/add_customer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ service_type: selectedService, is_priority: isPriority })
                });
                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Failed to generate queue number');
                }

                // Populate ticket
                document.getElementById('resultQueue').textContent = data.queue_number;

                const serviceLabels = {
                    bills: 'Bills / Payment',
                    complaints: 'Complaints',
                    customer_service: 'Customer Service'
                };
                document.getElementById('resultService').textContent =
                    serviceLabels[selectedService] || selectedService;

                const priorityBadge = document.getElementById('resultPriority');
                if (isPriority) {
                    priorityBadge.classList.remove('hidden');
                } else {
                    priorityBadge.classList.add('hidden');
                }

                document.getElementById('resultTime').textContent =
                    'Generated at ' + new Date().toLocaleTimeString();

                // Fetch updated wait times
                try {
                    const wtRes  = await fetch('api/get_wait_times.php');
                    const wtData = await wtRes.json();
                    if (wtData.success) {
                        document.getElementById('resultWaiting').textContent  = wtData.waiting_count ?? '—';
                        document.getElementById('resultEstWait').textContent  = wtData.estimated_wait_time ?? '—';
                    }
                } catch (_) {}

                // Show result step
                document.getElementById('stepForm').classList.add('hidden');
                document.getElementById('stepResult').classList.remove('hidden');

            } catch (err) {
                showError(err.message || 'An error occurred. Please try again.');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-ticket-alt mr-2"></i>Generate Queue Number';
            }
        }

        function resetForm() {
            selectedService = '';
            document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
            if (priorityActive) togglePriority();
            hideError();
            document.getElementById('stepResult').classList.add('hidden');
            document.getElementById('stepForm').classList.remove('hidden');
            loadWaitTimes();
        }

        async function loadWaitTimes() {
            try {
                const res  = await fetch('api/get_wait_times.php');
                const data = await res.json();
                if (data.success) {
                    const mins = data.estimated_wait_time;
                    document.getElementById('waitText').textContent =
                        mins > 0 ? `Approximately ${mins} minute${mins !== 1 ? 's' : ''}` : 'No wait — serve immediately';
                    document.getElementById('waitCount').textContent = data.waiting_count ?? '—';
                }
            } catch (_) {
                document.getElementById('waitText').textContent = 'Unable to fetch wait time';
            }
        }

        loadWaitTimes();
        setInterval(loadWaitTimes, 15000);
    </script>
</body>
</html>
