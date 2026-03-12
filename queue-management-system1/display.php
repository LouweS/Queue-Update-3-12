<?php 
include 'config.php';

// Get display settings
try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->exec("ALTER TABLE display_settings ADD COLUMN IF NOT EXISTS ad_video_url TEXT NULL");
    $conn->exec("ALTER TABLE display_settings ADD COLUMN IF NOT EXISTS announcement_text TEXT NULL");
    $conn->exec("ALTER TABLE display_settings ADD COLUMN IF NOT EXISTS news_announcements TEXT NULL");
    $stmt = $conn->query("SELECT * FROM display_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $settings = [
        'company_name' => 'Customer Service',
        'welcome_message' => 'Welcome to our Service Center',
        'ad_video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
        'announcement_text' => 'Please prepare your queue number and wait for your turn.',
        'news_announcements' => ''
    ];
}

$adVideoUrl = !empty($settings['ad_video_url'])
    ? $settings['ad_video_url']
    : 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4';

$announcementText = !empty($settings['announcement_text'])
    ? $settings['announcement_text']
    : 'Please prepare your queue number and wait for your turn.';

$newsAnnouncements = !empty($settings['news_announcements'])
    ? $settings['news_announcements']
    : "Water service interruption on March 15, 2026 from 8:00 AM - 12:00 PM for maintenance.\nSenior citizens may ask staff for priority lane assistance.\nReport water leaks immediately. Call our emergency hotline 24/7.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Display</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --blue-main: #0a4e86;
            --blue-dark: #0c6fb3;
            --panel-bg: #e9f6ff;
            --text-main: #0b3557;
            --text-soft: #35698f;
            --panel-border: #8bc6eb;
            --white: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Tahoma, Verdana, sans-serif;
            background: var(--blue-main);
            color: var(--text-main);
            overflow: hidden;
        }
        .screen {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .topbar {
            height: 58px;
            background: var(--blue-dark);
            color: #ffffff;
            display: grid;
            grid-template-columns: 1fr auto auto;
            align-items: center;
            gap: 18px;
            padding: 0 16px;
            font-weight: 700;
            border-bottom: 2px solid rgba(255, 255, 255, 0.35);
            flex-shrink: 0;
        }
        .top-company {
            font-size: 1.3rem;
        }
        .top-date {
            font-size: 1.15rem;
            letter-spacing: 0.2px;
        }
        .top-time {
            font-size: 1.15rem;
            font-family: Consolas, monospace;
            white-space: nowrap;
        }
        .layout {
            flex: 1;
            display: grid;
            grid-template-columns: 63% 37%;
            gap: 10px;
            padding: 10px;
            min-height: 0;
        }
        .panel {
            background: var(--panel-bg);
            border: 2px solid var(--panel-border);
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(7, 42, 73, 0.2);
        }
        .left-column {
            display: grid;
            grid-template-rows: 1fr auto auto;
            gap: 10px;
            min-height: 0;
        }
        .video-card {
            padding: 0;
            min-height: 0;
            background: transparent;
            border: none;
            border-radius: 0;
            box-shadow: none;
        }
        .video-shell {
            width: 100%;
            height: 100%;
            background: #000000;
            border: none;
            border-radius: 0;
            overflow: hidden;
        }
        .video-shell video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .section-card {
            padding: 12px 14px;
        }
        .section-title {
            text-align: center;
            color: var(--text-main);
            font-size: 1.75rem;
            letter-spacing: 0.6px;
            font-weight: 700;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .announcement-body {
            background: #f7fcff;
            border: 1px solid #b7dbf2;
            border-radius: 6px;
            padding: 8px 10px;
            min-height: 60px;
            overflow: hidden;
            font-size: 1.15rem;
            line-height: 1.6;
        }
        .announcement-line {
            color: #12486f;
            margin-bottom: 6px;
            font-weight: 500;
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border: 1px solid #b7dbf2;
            border-radius: 6px;
            overflow: hidden;
        }
        .contact-col {
            background: #f4fbff;
            padding: 8px;
        }
        .contact-col + .contact-col {
            border-left: 1px solid #b7dbf2;
        }
        .contact-row {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-bottom: 6px;
            font-size: 0.9rem;
        }
        .contact-row:last-child { margin-bottom: 0; }
        .contact-icon {
            color: #0c6fb3;
            font-size: 1rem;
            margin-top: 2px;
            width: 16px;
            flex-shrink: 0;
        }
        .contact-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #13517c;
            line-height: 1;
        }
        .contact-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-main);
        }
        .right-column {
            display: grid;
            grid-template-rows: auto 1fr auto;
            gap: 10px;
            min-height: 0;
        }
        .now-serving-card {
            padding: 14px;
            text-align: center;
        }
        .now-title {
            font-size: 1.6rem;
            color: #0f4d78;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .now-queue {
            font-size: 4rem;
            font-weight: 700;
            color: #07558a;
            letter-spacing: 3px;
            font-family: Consolas, monospace;
            line-height: 1;
            margin-bottom: 6px;
            min-height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .now-proceed {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-soft);
        }
        .counter-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            min-width: 0;
            align-content: stretch;
        }
        .counter-card {
            background: linear-gradient(145deg, #ffffff, #dff1ff);
            border: 2px solid #7cb8df;
            border-radius: 14px;
            text-align: center;
            padding: 18px 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(30, 63, 134, 0.18);
            transition: transform 0.15s ease;
        }
        .counter-card:hover {
            transform: translateY(-2px);
        }
        .counter-name {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f527f;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 0.8px;
            line-height: 1.2;
        }
        .counter-queue {
            font-size: 4.2rem;
            line-height: 1;
            font-family: Consolas, monospace;
            color: #005f96;
            font-weight: 900;
            display: flex;
            align-items: center;
            justify-content: center;
            text-shadow: 0 1px 5px rgba(0, 95, 150, 0.15);
        }
        .waiting-card {
            padding: 14px 16px;
        }
        .waiting-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 8px;
            padding: 0 2px;
        }
        .waiting-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f4d78;
            text-transform: uppercase;
            line-height: 1;
            letter-spacing: 0.5px;
        }
        .waiting-next {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-soft);
        }
        .waiting-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .waiting-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f5fbff;
            border: 1px solid #a9d2ee;
            border-radius: 10px;
            padding: 14px 10px;
            min-height: 70px;
        }
        .waiting-num {
            font-size: 0.8rem;
            font-weight: 700;
            color: #3d7ca8;
            text-align: center;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .waiting-queue-num {
            font-size: 1.8rem;
            font-weight: 800;
            color: #005f96;
            font-family: Consolas, monospace;
        }
        .waiting-empty {
            text-align: center;
            color: #5d95bb;
            font-size: 0.9rem;
            padding: 12px 0;
        }
    </style>
</head>
<body>
    <div class="screen">
        <div class="topbar">
            <div class="top-company"><?php echo htmlspecialchars($settings['company_name'] ?? 'Customer Service'); ?></div>
            <div id="topDate" class="top-date">Wednesday, March 11, 2026</div>
            <div id="topTime" class="top-time">08:02:44 AM</div>
        </div>

        <div class="layout">
            <section class="left-column">
                <div class="panel video-card">
                    <div class="video-shell">
                        <video id="displayAdVideo" autoplay muted loop playsinline>
                            <source id="displayAdSource" src="<?php echo htmlspecialchars($adVideoUrl); ?>" type="video/mp4">
                        </video>
                    </div>
                </div>

                <div class="panel section-card">
                    <div class="section-title">Announcement</div>
                    <div id="announcementBody" class="announcement-body">
                        <?php
                        $linesFromNews = array_values(array_filter(explode("\n", $newsAnnouncements), function($line) {
                            return trim($line) !== '';
                        }));
                        $linesFromAnnouncement = array_values(array_filter(explode("\n", $announcementText), function($line) {
                            return trim($line) !== '';
                        }));
                        $initialLines = !empty($linesFromNews) ? $linesFromNews : $linesFromAnnouncement;
                        if (empty($initialLines)) {
                            $initialLines = ['Please prepare your queue number and wait for your turn.'];
                        }
                        foreach ($initialLines as $line) {
                            echo '<div class="announcement-line">' . htmlspecialchars(trim($line)) . '</div>';
                        }
                        ?>
                    </div>
                </div>

                <div class="panel section-card">
                    <div class="section-title">Contact & Hours</div>
                    <div class="contact-grid">
                        <div class="contact-col">
                            <div class="contact-row">
                                <i class="fas fa-phone-alt contact-icon"></i>
                                <div>
                                    <div class="contact-label">Hotline</div>
                                    <div class="contact-value">(123) 456-7890</div>
                                </div>
                            </div>
                            <div class="contact-row">
                                <i class="fas fa-phone contact-icon"></i>
                                <div>
                                    <div class="contact-label">Emergency</div>
                                    <div class="contact-value">(123) 456-HELP</div>
                                </div>
                            </div>
                        </div>
                        <div class="contact-col">
                            <div class="contact-row">
                                <i class="far fa-clock contact-icon"></i>
                                <div>
                                    <div class="contact-label">Hours</div>
                                    <div class="contact-value">8:00AM-5:00PM</div>
                                </div>
                            </div>
                            <div class="contact-row">
                                <i class="fas fa-globe contact-icon"></i>
                                <div>
                                    <div class="contact-label">Website</div>
                                    <div class="contact-value">waterdistrict.gov.ph</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="right-column">
                <div class="panel now-serving-card">
                    <div class="now-title">Now Serving</div>
                    <div id="heroNowServing" class="now-queue">---</div>
                    <div id="heroProceed" class="now-proceed">Please wait for your number to be called</div>
                </div>

                <div id="counterCards" class="counter-grid">
                    <div class="counter-card">
                        <div class="counter-name">CASHIER 1</div>
                        <div class="counter-queue">---</div>
                    </div>
                    <div class="counter-card">
                        <div class="counter-name">CASHIER 2</div>
                        <div class="counter-queue">---</div>
                    </div>
                    <div class="counter-card">
                        <div class="counter-name">CUSTOMER SERVICE 3</div>
                        <div class="counter-queue">---</div>
                    </div>
                    <div class="counter-card">
                        <div class="counter-name">CUSTOMER SERVICE 4</div>
                        <div class="counter-queue">---</div>
                    </div>
                    <div class="counter-card">
                        <div class="counter-name">Counter 5</div>
                        <div class="counter-queue">---</div>
                    </div>
                    <div class="counter-card">
                        <div class="counter-name">Counter 6</div>
                        <div class="counter-queue">---</div>
                    </div>
                </div>

                <div class="panel waiting-card">
                    <div class="waiting-header">
                        <div class="waiting-title">Waiting List</div>
                        <div id="waitingNext" class="waiting-next">Next: ---</div>
                    </div>
                    <div id="waitingList" class="waiting-list"></div>
                </div>
            </section>
        </div>

        <audio id="notificationSound" preload="auto">
            <source src="https://assets.mixkit.co/sfx/preview/mixkit-correct-answer-tone-2870.mp3" type="audio/mpeg">
        </audio>
    </div>

    <script src="js/display.js"></script>
</body>
</html>