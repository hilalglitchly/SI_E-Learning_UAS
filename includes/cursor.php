<!-- Custom Cursor -->
<div id="custom-cursor"></div>
<script>
    // --- 1. Sound Effect System (Web Audio API - 8-bit Retro Click) ---
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

    // --- 1. Sound Effect System (Variasi File Audio Eksternal) ---
    const clickSounds = [
        new Audio('https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3'), // Pop click
        new Audio('https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3'), // Light click
        new Audio('https://assets.mixkit.co/active_storage/sfx/2570/2570-preview.mp3'), // Soft tap
        new Audio('https://assets.mixkit.co/active_storage/sfx/1114/1114-preview.mp3')  // Tiny sweep click
    ];

    // Preload audio files & atur volume
    clickSounds.forEach(audio => {
        audio.volume = 0.6; // Volume 60%
        audio.load(); // Memaksa browser mendownload sebagian data di awal (preload)
    });

    function playClickSound() {
        // Memilih satu suara secara acak (bervariasi tiap klik)
        const randomIndex = Math.floor(Math.random() * clickSounds.length);
        const sound = clickSounds[randomIndex];
        
        // Reset waktu ke 0 agar suara bisa ditumpuk (diputar cepat berturut-turut)
        sound.currentTime = 0;
        sound.play().catch(err => console.log('Audio diputar otomatis diblokir browser: ', err));
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