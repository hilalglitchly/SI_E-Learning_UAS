<!-- Custom Cursor -->
<div id="custom-cursor"></div>
<script>
    // --- 1. Sound Effect System (Web Audio API - 8-bit Retro Click) ---
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

    function playClickSound() {
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        
        // Membuat suara "klik" plastik/mekanis realistis menggunakan White Noise
        const bufferSize = audioCtx.sampleRate * 0.015; // Sangat singkat: 15 milidetik
        const buffer = audioCtx.createBuffer(1, bufferSize, audioCtx.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < bufferSize; i++) {
            data[i] = Math.random() * 2 - 1; // Menghasilkan noise (statis)
        }
        
        const noiseSource = audioCtx.createBufferSource();
        noiseSource.buffer = buffer;
        
        // Filter agar suaranya tajam seperti plastik (Highpass filter)
        const filter = audioCtx.createBiquadFilter();
        filter.type = 'highpass';
        filter.frequency.value = 4000;
        
        // Membungkus suara agar langsung menghilang (Envelope)
        const gainNode = audioCtx.createGain();
        gainNode.gain.setValueAtTime(0.7, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.015);
        
        noiseSource.connect(filter);
        filter.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        noiseSource.start();
    }

    document.addEventListener('click', function(e) {
        const target = e.target.closest('a, button, input[type="submit"], input[type="button"], .brutal-hover, .neo-btn, select, label');
        if (target) {
            playClickSound();
        }
    });

    // --- 2. Custom Cursor System ---
    document.addEventListener('DOMContentLoaded', () => {
        if (!document.getElementById('custom-cursor')) return;
        const cursor = document.getElementById('custom-cursor');
        
        // Disable JS cursor on touch devices
        if (window.matchMedia && !window.matchMedia("(pointer: fine)").matches) {
            cursor.style.display = 'none';
            return;
        }

        document.addEventListener('mousemove', e => {
            cursor.style.left = e.clientX + 'px';
            cursor.style.top = e.clientY + 'px';
        });
        document.querySelectorAll('a, button, .brutal-hover, .neo-btn, input[type="submit"], input[type="button"], select, label').forEach(el => {
            el.addEventListener('mouseenter', () => cursor.classList.add('hover'));
            el.addEventListener('mouseleave', () => cursor.classList.remove('hover'));
        });
    });
</script>