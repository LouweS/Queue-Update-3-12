let lastHeroQueue = '';

function updateDisplayTime() {
    const now = new Date();
    const topDate = document.getElementById('topDate');
    const topTime = document.getElementById('topTime');

    if (topDate) {
        topDate.textContent = now.toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    }

    if (topTime) {
        topTime.textContent = now.toLocaleTimeString('en-US', {
            hour12: true,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    }
}

function playNotificationSound() {
    const audio = document.getElementById('notificationSound');
    if (!audio) return;
    audio.currentTime = 0;
    audio.play().catch(() => {});
}

function renderNowServing(data) {
    const heroNowServing = document.getElementById('heroNowServing');
    const heroProceed = document.getElementById('heroProceed');
    if (!heroNowServing || !heroProceed) return;

    const queueNumber = data.now_serving && data.now_serving.queue_number ? data.now_serving.queue_number : '---';
    
    let counterText = 'Please wait for your number to be called';
    if (data.now_serving && data.now_serving.counter_name) {
        const counterMatch = String(data.now_serving.counter_name).match(/\d+/);
        if (counterMatch) {
            counterText = `Please proceed to Counter ${counterMatch[0]}`;
        } else {
            counterText = `Please proceed to ${data.now_serving.counter_name}`;
        }
    }

    heroNowServing.textContent = queueNumber;
    heroProceed.textContent = counterText;

    if (queueNumber !== '---' && queueNumber !== lastHeroQueue) {
        if (lastHeroQueue !== '') {
            playNotificationSound();
        }
        lastHeroQueue = queueNumber;
    }
}

function renderCounterCards(data) {
    const counterCards = document.getElementById('counterCards');
    if (!counterCards) return;

    const cards = [];
    
    // Filter to only online counters
    const onlineCounters = Array.isArray(data.counters)
        ? data.counters.filter(counter => counter.is_online)
        : [];

    // Use 3 columns for 5-6 counters so they fit in 2 rows and the waiting list stays visible
    const cols = onlineCounters.length >= 5 ? 3 : 2;
    counterCards.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
    
    onlineCounters.forEach(counter => {
        const displayText = counter.current_queue_number ? counter.current_queue_number : '---';
        // Shrink font slightly when 5-6 counters to keep cards compact
        const queueFontSize = onlineCounters.length >= 5 ? '3rem' : '4.2rem';
        cards.push(`
            <div class="counter-card" style="--q-font: ${queueFontSize}">
                <div class="counter-name">${counter.name}</div>
                <div class="counter-queue" style="font-size: ${queueFontSize}">${displayText}</div>
            </div>
        `);
    });

    // Show fallback message if no counters are online
    if (cards.length === 0) {
        counterCards.style.gridTemplateColumns = 'repeat(2, 1fr)';
        counterCards.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; color: #5d95bb; padding: 20px;">All counters offline</div>';
    } else {
        counterCards.innerHTML = cards.join('');
    }
}

function renderWaitingList(data) {
    const waitingNext = document.getElementById('waitingNext');
    const waitingList = document.getElementById('waitingList');
    if (!waitingNext || !waitingList) return;

    const queue = Array.isArray(data.waiting_queue)
        ? data.waiting_queue.map(item => item.queue_number).filter(Boolean)
        : [];

    waitingNext.textContent = `Next: ${queue.length > 0 ? queue[0] : '---'}`;

    const items = [];
    for (let i = 1; i <= 4; i++) {
        const queueNum = queue[i - 1] || '';
        items.push(`
            <div class="waiting-row">
                <div class="waiting-num">#${i}</div>
                <div class="waiting-queue-num">${queueNum || '--'}</div>
            </div>
        `);
    }

    waitingList.innerHTML = items.join('');
}

function renderAnnouncements(data) {
    const announcementBody = document.getElementById('announcementBody');
    if (!announcementBody) return;

    const linesFromNews = String(data.news_announcements || '')
        .split('\n')
        .map(line => line.trim())
        .filter(Boolean);

    const linesFromAnnouncement = String(data.announcement_text || '')
        .split('\n')
        .map(line => line.trim())
        .filter(Boolean);

    const linesToShow = !linesFromNews.length ? linesFromAnnouncement : linesFromNews;
    
    announcementBody.innerHTML = linesToShow
        .map(line => `<div class="announcement-line">${line}</div>`)
        .join('');
}

async function updateDisplay() {
    try {
        const response = await fetch(`api/get_display_data.php?_=${Date.now()}`, {
            headers: {
                'Cache-Control': 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma': 'no-cache'
            }
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (!data || typeof data !== 'object') throw new Error('Invalid data');

        renderNowServing(data);
        renderCounterCards(data);
        renderWaitingList(data);
        renderAnnouncements(data);

        const adVideoSource = document.getElementById('displayAdSource');
        const adVideoElement = document.getElementById('displayAdVideo');
        if (adVideoSource && adVideoElement && data.ad_video_url && data.ad_video_url.trim() !== '') {
            if (adVideoSource.getAttribute('src') !== data.ad_video_url) {
                adVideoSource.setAttribute('src', data.ad_video_url);
                adVideoElement.load();
            }
        }

    } catch (error) {
        console.error('Display update error:', error);
        document.getElementById('heroNowServing').textContent = '---';
        document.getElementById('heroProceed').textContent = 'Please wait for your number to be called';
        const waitingList = document.getElementById('waitingList');
        if (waitingList) {
            waitingList.innerHTML = Array.from({length: 7}, (_, i) => `
                <div class="waiting-row">
                    <div class="waiting-num">${i + 1}</div>
                    <div class="waiting-queue-num"></div>
                </div>
            `).join('');
        }
    }
}

function safeUpdateDisplay() {
    try {
        updateDisplay();
    } catch (e) {
        console.error('Safe update failed:', e);
    }
}

setInterval(updateDisplayTime, 1000);
updateDisplayTime();
updateDisplay();
setInterval(safeUpdateDisplay, 3000);