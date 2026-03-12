<?php
$counterId = isset($_GET['counter_id']) ? (int)$_GET['counter_id'] : 0;
if ($counterId < 1) {
    $counterId = 1;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counter Details</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --wd-blue-900: #0b2f6b;
            --wd-blue-700: #1d56b3;
            --wd-page: #f3f8ff;
        }
        .gradient-bg {
            background: linear-gradient(135deg, var(--wd-blue-900) 0%, var(--wd-blue-700) 100%);
        }
        .panel {
            border: 1px solid #d8e8ff;
            box-shadow: 0 8px 24px rgba(17, 64, 138, 0.08);
        }
        .queue-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-gray-50" style="background: var(--wd-page);">
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-6 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold"><i class="fas fa-desktop mr-3"></i><span id="counterTitle">Counter</span></h1>
                <p class="text-sm opacity-90" id="counterServices">Loading service types...</p>
            </div>
            <a href="index.php" class="bg-white text-blue-800 px-4 py-2 rounded-lg font-semibold hover:bg-blue-50 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </header>

    <main class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-lg p-6 panel">
                <p class="text-gray-600 text-sm">Status</p>
                <h2 id="counterStatus" class="text-2xl font-bold text-gray-800">-</h2>
            </div>
            <div class="bg-white rounded-lg p-6 panel">
                <p class="text-gray-600 text-sm">Now Serving</p>
                <h2 id="nowServing" class="text-2xl font-bold text-blue-700 queue-number">-</h2>
            </div>
            <div class="bg-white rounded-lg p-6 panel">
                <p class="text-gray-600 text-sm">Waiting (Eligible)</p>
                <h2 id="waitingCount" class="text-2xl font-bold text-gray-800">0</h2>
            </div>
        </div>

        <div class="bg-white rounded-lg p-6 panel mb-6">
            <div class="flex flex-wrap gap-3">
                <button id="callNextBtn" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-phone mr-2"></i>Call Next
                </button>
                <button id="completeBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-check mr-2"></i>Complete Current
                </button>
                <button id="toggleBtn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-power-off mr-2"></i>Deactivate
                </button>
                <button id="refreshBtn" class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded-lg font-semibold transition">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>

        <div class="bg-white rounded-lg p-6 panel">
            <h3 class="text-xl font-bold text-gray-800 mb-4"><i class="fas fa-list mr-2"></i>Queue For This Counter</h3>
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Queue No.</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Service</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Time</th>
                        </tr>
                    </thead>
                    <tbody id="counterQueueTable" class="divide-y divide-gray-200"></tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        window.COUNTER_ID = <?php echo (int)$counterId; ?>;
    </script>
    <script src="js/counter.js"></script>
</body>
</html>
