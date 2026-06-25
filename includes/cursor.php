<!-- Custom Cursor -->
<div id="custom-cursor"></div>
<script>
    // --- 1. Sound Effect System (Web Audio API - 8-bit Retro Click) ---
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

    function playClickSound() {
        if (audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.type = 'square';
        oscillator.frequency.setValueAtTime(200, audioCtx.currentTime);
        oscillator.frequency.exponentialRampToValueAtTime(50, audioCtx.currentTime + 0.1);
        
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.1);
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