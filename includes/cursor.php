<!-- Custom Cursor -->
<div id="custom-cursor"></div>
<script>
    // --- 1. Sound Effect System (Variasi File Audio Eksternal via RAM) ---
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const audioBuffers = [];
    
    const soundUrls = [
        'https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3', // Pop click
        'https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3', // Light click
        'https://assets.mixkit.co/active_storage/sfx/2570/2570-preview.mp3', // Soft tap
        'https://assets.mixkit.co/active_storage/sfx/1114/1114-preview.mp3'  // Tiny sweep click
    ];

    // Download audio sekali saja dan simpan murni di RAM (AudioBuffer)
    soundUrls.forEach(url => {
        fetch(url)
            .then(response => response.arrayBuffer())
            .then(arrayBuffer => audioCtx.decodeAudioData(arrayBuffer))
            .then(audioBuffer => {
                audioBuffers.push(audioBuffer);
            })
            .catch(err => console.log('Gagal memuat audio:', err));
    });

    function playClickSound() {
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        
        // Jangan putar jika audio belum selesai di-download
        if (audioBuffers.length === 0) return; 

        // Memilih satu suara secara acak
        const randomIndex = Math.floor(Math.random() * audioBuffers.length);
        const source = audioCtx.createBufferSource();
        source.buffer = audioBuffers[randomIndex];
        
        // Mengatur volume
        const gainNode = audioCtx.createGain();
        gainNode.gain.value = 0.6; 
        
        source.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        // Langsung putar dari RAM (0 Latency, anti-spam failure)
        source.start(0);
    }

    // Menggunakan mousedown agar lebih responsif sebelum tombol benar-benar dilepas
    document.addEventListener('mousedown', function(e) {
        // Seleksi yang sangat luas untuk menangkap semua interaksi
        const target = e.target.closest('a, button, input[type="submit"], input[type="button"], input[type="radio"], input[type="checkbox"], select, label, .brutal-hover, .neo-btn, .neo-box, .btn-small, .btn-accent, .nav-item, summary, .course-card');
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