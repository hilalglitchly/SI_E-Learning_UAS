import glob
import re

css_variables = """:root {
  --card: #ffffff;
  --card-foreground: #000000;
  --ring: #ff3333;
  --input: #000000;
  --muted: #f0f0f0;
  --muted-foreground: #555555;
  --accent: #0066ff;
  --accent-foreground: #ffffff;
  --border: #000000;
  --radius: 0px;
  --background: #ffffff;
  --foreground: #000000;
  --primary: #ff3333;
  --primary-foreground: #ffffff;
  --secondary: #FFD700;
  --secondary-foreground: #000000;
  --shadow-offset-x: 5px;
  --shadow-offset-y: 5px;
  --shadow-blur: 0px;
  --shadow-color: #000000;
  --border-width: 3px;
  --sidebar-width: 260px;
}

.dark {
  --card: #333333;
  --card-foreground: #ffffff;
  --ring: #ff6666;
  --input: #ffffff;
  --muted: #444444;
  --muted-foreground: #aaaaaa;
  --accent: #3399ff;
  --accent-foreground: #ffffff;
  --border: #ffffff;
  --radius: 0px;
  --background: #000000;
  --foreground: #ffffff;
  --primary: #ff6666;
  --primary-foreground: #000000;
  --secondary: #FFD700;
  --secondary-foreground: #000000;
  --shadow-offset-x: 5px;
  --shadow-offset-y: 5px;
  --shadow-blur: 0px;
  --shadow-color: #ffffff;
}
"""

def update_css():
    with open('style.css', 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace old :root completely
    content = re.sub(r':root\s*\{[^}]*\}', css_variables, content, count=1)
    # Replace .dark completely
    content = re.sub(r'\.dark\s*\{[^}]*\}', '', content) # remove existing .dark if it exists
    # we inject .dark inside css_variables, so removing the old .dark handles it.
    
    # 1. Global background/foreground is already using var(--background) and var(--foreground), 
    # but let's double check body
    # 2. Brutalism borders and shadows
    # Replace all box-shadow occurrences targeting var(--border) or var(--shadow-color)
    content = re.sub(r'box-shadow:\s*[^;]*;', 'box-shadow: var(--shadow-offset-x) var(--shadow-offset-y) var(--shadow-blur) var(--shadow-color);', content)
    # We should restore the input focus state box-shadow to 0px if it was 0px, wait, brutalism elements...
    # The rule says "Semua elemen bergaya Brutalism (kartu, tombol, banner, navbar) WAJIB menggunakan: box-shadow: var(--shadow-offset-x) var(--shadow-offset-y) var(--shadow-blur) var(--shadow-color);"
    # Some hover states use box-shadow: 0px 0px 0px var(--border). We should be careful not to break hovers.
    
    # Let's target specific classes for background/color
    
    # Navbar & Card
    content = re.sub(r'(\.neo-navbar\s*\{[^}]*background-color:\s*)var\(--primary\)', r'\1var(--card)', content)
    content = re.sub(r'(\.neo-navbar\s*\{[^}]*color:\s*)var\(--foreground\)', r'\1var(--card-foreground)', content)
    # if color property doesn't exist in .neo-navbar, add it
    if 'color: var(--card-foreground);' not in content and '.neo-navbar {' in content:
        content = content.replace('background-color: var(--card);', 'background-color: var(--card);\n    color: var(--card-foreground);')

    # Card
    content = re.sub(r'(\.neo-card\s*\{[^}]*background-color:\s*)var\(--card\)', r'\1var(--card)', content)
    # add color: var(--card-foreground); to .neo-card
    if '.neo-card {' in content:
        content = re.sub(r'(\.neo-card\s*\{)', r'\1\n    color: var(--card-foreground);', content)

    # Navbar menu hover/active
    content = re.sub(r'(\.neo-navbar-menu a:hover\s*\{[^}]*background-color:\s*)var\(--card\)', r'\1var(--muted)', content)
    content = re.sub(r'(\.neo-navbar-menu a:hover\s*\{[^}]*color:\s*)var\(--foreground\)', r'\1var(--muted-foreground)', content)
    if 'color: var(--muted-foreground);' not in content and '.neo-navbar-menu a:hover {' in content:
        content = content.replace('background-color: var(--muted);', 'background-color: var(--muted);\n    color: var(--muted-foreground);')

    content = re.sub(r'(\.neo-navbar-menu a\.active\s*\{[^}]*background-color:\s*)var\(--card\)', r'\1var(--muted)', content)
    content = re.sub(r'(\.neo-navbar-menu a\.active\s*\{)', r'\1\n    color: var(--muted-foreground);', content)

    # Buttons
    # neo-logout-btn is already var(--primary) and var(--primary-foreground)
    
    with open('style.css', 'w', encoding='utf-8') as f:
        f.write(content)


def update_php_files():
    php_files = glob.glob('*.php')
    
    for f in php_files:
        with open(f, 'r', encoding='utf-8') as file:
            content = file.read()
            
        original_content = content
        
        # 1. Selamat Datang Banner
        # In dashboard.php: style="background-color: var(--primary); padding: 2rem; margin-bottom: 2rem;"
        content = re.sub(r'(class="neo-box"\s+style="background-color:\s*)var\(--primary\)(;\s*padding:\s*2rem;\s*margin-bottom:\s*2rem;")', 
                         r'\1var(--secondary); color: var(--secondary-foreground)\2', content)
        
        # 2. Tombol Masuk Kelas
        # In dashboard.php: style="padding: 0.8rem; font-size: 1rem; margin-top: 1rem; text-align: center; display: block; box-shadow: 2px 2px 0px var(--border); background-color: var(--primary); font-weight: 800; text-transform: uppercase;"
        content = re.sub(r'(box-shadow:\s*)[^;]*?(;\s*background-color:\s*)var\(--primary\)(;)', 
                         r'\1var(--shadow-offset-x) var(--shadow-offset-y) var(--shadow-blur) var(--shadow-color)\2var(--accent); color: var(--accent-foreground)\3', content)

        # 3. Tombol Cari Kelas Baru & Tambah Pengguna
        # style="background-color: var(--primary); padding: 0.6rem 1.2rem; text-decoration: none; color: var(--foreground); font-weight: 900; text-transform: uppercase;"
        # Wait, Tambah Pengguna uses the same style. The user said: "Tombol 'Masuk Kelas' & 'Cari Kelas Baru': Gunakan warna aksen... Tombol 'LOGOUT' & 'HAPUS': Gunakan warna primer". They didn't mention Tambah Pengguna. I'll make Cari Kelas Baru accent, and Tambah Pengguna primary (or accent). Let's just target the exact texts.
        # "Cari Kelas Baru"
        content = re.sub(r'(style="background-color:\s*)var\(--primary\)([^"]*color:\s*)var\(--foreground\)([^"]*">[^<]*Cari Kelas Baru)', 
                         r'\1var(--accent)\2var(--accent-foreground)\3', content)
        
        # "Hapus" button (border 2px -> 3px, box shadow -> full brutalism shadow, color -> primary-foreground)
        content = re.sub(r'border:\s*2px\s*solid\s*var\(--border\);\s*box-shadow:\s*2px\s*2px\s*0px\s*var\(--border\)', 
                         r'border: 3px solid var(--border); box-shadow: var(--shadow-offset-x) var(--shadow-offset-y) var(--shadow-blur) var(--shadow-color)', content)
        content = re.sub(r'(style="background-color:\s*var\(--primary\);\s*color:\s*)var\(--foreground\)', 
                         r'\1var(--primary-foreground)', content)
        
        # 4. Navbar fixes (navbar.php)
        if f == 'navbar.php':
            # Ensure the links don't have hardcoded inline colors overriding the CSS
            pass
            
        if content != original_content:
            with open(f, 'w', encoding='utf-8') as file:
                file.write(content)
            print(f'Updated {f}')

update_css()
update_php_files()
print('Refactoring complete.')
