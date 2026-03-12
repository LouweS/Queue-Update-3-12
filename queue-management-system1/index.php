<?php
require_once __DIR__ . '/config.php';

$displaySettings = [
    'ad_video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
    'announcement_text' => 'Please have your queue number ready.'
];

try {
    /** @var Database $db */
    $db = new Database();
    /** @var PDO $conn */
    $conn = $db->getConnection();
    $conn->exec("ALTER TABLE display_settings ADD COLUMN IF NOT EXISTS ad_video_url TEXT NULL");
    $conn->exec("ALTER TABLE display_settings ADD COLUMN IF NOT EXISTS announcement_text TEXT NULL");

    $stmt = $conn->query("SELECT ad_video_url, announcement_text FROM display_settings WHERE id = 1 LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $displaySettings['ad_video_url'] = $row['ad_video_url'] ?: $displaySettings['ad_video_url'];
        $displaySettings['announcement_text'] = $row['announcement_text'] ?: $displaySettings['announcement_text'];
    }
} catch (Exception $e) {
    // Keep defaults when settings table is not available.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Queuing System</title>
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
        .gradient-bg {
            background: linear-gradient(135deg, var(--wd-blue-900) 0%, var(--wd-blue-700) 100%);
        }
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 26px rgba(17, 64, 138, 0.18);
        }
        .queue-number {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }

        .wd-panel {
            border: 1px solid #d8e8ff;
            box-shadow: 0 8px 24px rgba(17, 64, 138, 0.08);
        }

        .wd-icon {
            background: linear-gradient(135deg, var(--wd-blue-100), var(--wd-sky-100));
            color: var(--wd-blue-700);
        }

        .wd-btn-primary {
            background: linear-gradient(135deg, var(--wd-blue-800), var(--wd-blue-700));
        }

        .wd-btn-primary:hover {
            filter: brightness(0.95);
        }

        footer {
            position: absolute;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-50" style="background: var(--wd-page);">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold"><i class="fas fa-users mr-3"></i>Queue Management System</h1>
                <div class="text-right">
                    <div id="current-time" class="text-xl font-mono"></div>
                    <div class="text-sm">Welcome, Admin</div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 card-hover wd-panel">
                <div class="flex items-center">
                    <div class="p-3 rounded-full wd-icon mr-4">
                        <i class="fas fa-clock text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="waiting-count">0</h3>
                        <p class="text-gray-600">Waiting</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover wd-panel">
                <div class="flex items-center">
                    <div class="p-3 rounded-full wd-icon mr-4">
                        <i class="fas fa-user-check text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="serving-count">0</h3>
                        <p class="text-gray-600">Serving</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover wd-panel">
                <div class="flex items-center">
                    <div class="p-3 rounded-full wd-icon mr-4">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="completed-count">0</h3>
                        <p class="text-gray-600">Completed</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover wd-panel">
                <div class="flex items-center">
                    <div class="p-3 rounded-full wd-icon mr-4">
                        <i class="fas fa-times-circle text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800" id="today-count">0</h3>
                        <p class="text-gray-600">Today's Total</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Add Customer Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 card-hover wd-panel">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6"><i class="fas fa-plus-circle mr-2"></i>Add New Customer</h2>
                    
                    <form id="customerForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Service Type</label>
                            <select id="serviceType" required 
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">Select service type</option>
                                <option value="bills">Bills</option>
                                <option value="complaints">Complaints</option>
                                <option value="customer_service">Customer Service</option>
                            </select>
                        </div>

                        <label class="flex items-center space-x-2 text-sm text-gray-700">
                            <input type="checkbox" id="isPriorityCustomer" class="h-4 w-4 text-blue-600 border-gray-300 rounded">
                            <span>Priority Customer</span>
                        </label>
                        
                        <button type="submit" 
                            class="w-full wd-btn-primary text-white py-3 px-4 rounded-lg transition duration-300 font-semibold">
                            <i class="fas fa-ticket-alt mr-2"></i>Generate Queue Number
                        </button>
                    </form>
                    
                    <!-- Generated Queue Display -->
                    <div id="queueResult" class="mt-6 hidden">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
                            <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">Queue Number Generated</h3>
                            <div class="text-3xl font-bold text-green-600 queue-number mb-2" id="generatedQueue"></div>
                            <p class="text-gray-600">Please wait for your number to be called</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6 card-hover mt-6 wd-panel">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6"><i class="fas fa-tv mr-2"></i>Display Settings</h2>
                    <form id="displaySettingsForm" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Advertisement Video URL</label>
                            <input
                                type="text"
                                id="adVideoUrl"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="https://example.com/video.mp4 or uploads/ads/video.mp4"
                                value="<?php echo htmlspecialchars($displaySettings['ad_video_url']); ?>"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Local Advertisement Video</label>
                            <input
                                type="file"
                                id="adVideoFile"
                                accept="video/mp4,video/webm,video/ogg"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            >
                            <p class="text-xs text-gray-500 mt-1">If a file is selected, it will be uploaded and used automatically.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Announcement Text</label>
                            <textarea
                                id="announcementText"
                                rows="3"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Type announcement text for display screen"
                            ><?php echo htmlspecialchars($displaySettings['announcement_text']); ?></textarea>
                        </div>

                        <button
                            type="submit"
                            class="w-full wd-btn-primary text-white py-3 px-4 rounded-lg transition duration-300 font-semibold"
                        >
                            <i class="fas fa-save mr-2"></i>Save Display Settings
                        </button>
                    </form>
                </div>
            </div>

            <!-- Queue Management -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-6 card-hover wd-panel">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-list mr-2"></i>Queue Management</h2>
                        <div class="flex space-x-2">
                            <button onclick="refreshQueue()" class="wd-btn-primary text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-sync-alt mr-2"></i>Refresh
                            </button>
                            <button onclick="resetQueue()" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">
                                <i class="fas fa-redo mr-2"></i>Reset Queue
                            </button>
                        </div>
                    </div>

                    <!-- Counter Status -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Counter Management - Activate/Deactivate Counters</h3>
                        <div id="countersStatus" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Counters will be loaded here -->
                        </div>
                    </div>

                    <!-- Queue List -->
                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Queue No.</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Service</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Status</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Time</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="queueTable" class="divide-y divide-gray-200">
                                <!-- Queue data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <script src="js/main.js"></script>
</body>
</html>