<!-- Custom Cursor -->
<div id="custom-cursor"></div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (!document.getElementById('custom-cursor')) return;
        const cursor = document.getElementById('custom-cursor');
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