(function () {
    'use strict';

    var VIDEO_VOLUME = 0.1;
    var VIDEO_DUCK = 0.02;
    var BUZZER_VOLUME = 0.6;

    var previous = null;              
    var currentVideoPath = window.NOW_SERVING.videoPath || '';
    var soundOn = false;
    var queue = [];         
    var speaking = false;
    var failures = 0;

    var video = document.getElementById('promoVideo');
    var buzzer = document.getElementById('buzzerSound');
    var soundBanner = document.getElementById('soundBanner');
    var offlineBanner = document.getElementById('offlineBanner');

    function enableSound() {
        soundOn = true;
        soundBanner.style.display = 'none';
        if (video) { video.muted = false; video.volume = VIDEO_VOLUME; video.play().catch(function () {}); }

        buzzer.muted = true;
        buzzer.play().then(function () { buzzer.pause(); buzzer.currentTime = 0; buzzer.muted = false; }).catch(function () { buzzer.muted = false; });
        window.speechSynthesis.getVoices();
    }
    function checkAutoplay() {
        if (navigator.getAutoplayPolicy) {
            var p = navigator.getAutoplayPolicy('mediaelement');
            if (p === 'allowed') { enableSound(); return; }
        }
        soundBanner.style.display = 'block';
    }
    document.addEventListener('click', function () { if (!soundOn) enableSound(); });
    document.addEventListener('keydown', function () { if (!soundOn) enableSound(); });
    document.addEventListener('touchstart', function () { if (!soundOn) enableSound(); });
    checkAutoplay();

    function updateTime() {
        var now = new Date();
        var d = now.toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Manila' });
        var t = now.toLocaleString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true, timeZone: 'Asia/Manila' }).toUpperCase();
        document.getElementById('datetime').textContent = d + ' ' + t;
    }
    setInterval(updateTime, 1000);

    function nextAnnouncement() {
        if (speaking || !queue.length) return;
        var item = queue.shift();
        speaking = true;
        if (video) video.volume = VIDEO_DUCK;

        var line = 'Ticket number ' + item.ticket + ', please proceed to ' + item.spoken + '. ';
        var message = line + line;

        function done() {
            speaking = false;
            if (!queue.length && video) video.volume = VIDEO_VOLUME;
            nextAnnouncement();
        }
        function speak() {
            try {
                window.speechSynthesis.cancel();
                var speech = new SpeechSynthesisUtterance(message);
                speech.lang = 'en-US'; speech.rate = 0.80; speech.pitch = 1.25; speech.volume = 1;
                var voices = window.speechSynthesis.getVoices();
                var preferred = ['Microsoft Susan', 'Microsoft Aria', 'Microsoft Zira', 'Google US English', 'Samantha'];
                var selected = voices.find(function (v) { return preferred.some(function (n) { return v.name.indexOf(n) !== -1; }); });
                if (selected) speech.voice = selected;
                speech.onend = done;
                speech.onerror = done;
                window.speechSynthesis.speak(speech);
                setTimeout(function () { if (speaking && !window.speechSynthesis.speaking) done(); }, 20000);   
            } catch (e) { done(); }
        }

        buzzer.volume = BUZZER_VOLUME;
        buzzer.currentTime = 0;
        buzzer.onended = speak;
        buzzer.play().catch(function () {
            soundBanner.style.display = 'block';
            soundOn = false;
            speaking = false;
        });
    }
    function announce(t) {
        queue.push({ ticket: t.ticket, spoken: t.spoken });
        nextAnnouncement();
    }

    function render(tellers) {
        var row = document.getElementById('counterRow');
        var wanted = tellers.map(function (t) { return t.key; }).join('|');
        if (row.getAttribute('data-keys') !== wanted) {
            row.innerHTML = '';
            tellers.forEach(function (t) {
                var box = document.createElement('div');
                box.className = 'teller-box'; box.setAttribute('data-teller', t.key);
                var label = document.createElement('div'); label.className = 'counter-label'; label.textContent = t.label;
                var counter = document.createElement('div'); counter.className = 'counter';
                var p = document.createElement('p'); p.textContent = '_'; counter.appendChild(p);
                box.appendChild(label); box.appendChild(counter); row.appendChild(box);
            });
            row.setAttribute('data-keys', wanted);
        }

        tellers.forEach(function (t) {
            var box = row.querySelector('.teller-box[data-teller="' + t.key + '"]');
            if (!box) return;
            var p = box.querySelector('.counter p');
            var ticket = t.ticket || '_';
            var prev = previous ? previous[t.key] : null;
            var changed = prev && (prev.ticket !== t.ticket || prev.recall !== t.recall);
            p.textContent = ticket;
            p.setAttribute('data-len', String(ticket.length));
            if (changed && t.ticket) {
                announce(t);
                var counter = box.querySelector('.counter');
                counter.classList.remove('bounce');
                void counter.offsetWidth;
                counter.classList.add('bounce');
            }
        });

        var snapshot = {};
        tellers.forEach(function (t) { snapshot[t.key] = { ticket: t.ticket, recall: t.recall }; });
        previous = snapshot;
    }

    function fetchNowServing() {
        fetch('api/fetch_now_serving.php', { cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                failures = 0;
                offlineBanner.style.display = 'none';
                if (d && d.tellers) render(d.tellers);
            })
            .catch(function () {
                failures++;
                if (failures >= 3) offlineBanner.style.display = 'block';
            });
    }
    render(window.NOW_SERVING.initial);
    setInterval(fetchNowServing, 2000);

    setInterval(function () {
        fetch('api/fetch_latest_video.php', { cache: 'no-store' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.video_path && d.video_path !== currentVideoPath) {
                    var v = document.getElementById('promoVideo');
                    if (v) { v.src = d.video_path; v.load(); v.play().catch(function () {}); }
                    else { location.reload(); }
                    currentVideoPath = d.video_path;
                }
            })
            .catch(function () {});
    }, 10000);

    setInterval(function () { if (!speaking && !queue.length) location.reload(); }, 6 * 60 * 60 * 1000);
})();
