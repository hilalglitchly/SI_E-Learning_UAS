import glob
import re

css_variables = """:root {
  --card: #ffffff;
  --ring: #ff3333;
  --input: #000000;
  --muted: #f0f0f0;
  --accent: #0066ff;
  --border: #000000;
  --radius: 0px;
  --background: #ffffff;
  --foreground: #000000;
  --primary: #ff3333;
  --primary-foreground: #ffffff;
  --border-width: 3px;
  --shadow-offset: 5px;
  --sidebar-width: 260px;
}

.dark {
  --card: #333333;
  --ring: #ff6666;
  --input: #ffffff;
  --muted: #333333;
  --accent: #3399ff;
  --border: #ffffff;
  --radius: 0px;
  --background: #000000;
  --foreground: #ffffff;
  --primary: #ff6666;
  --primary-foreground: #000000;
}
"""

def update_css():
    with open('style.css', 'r', encoding='utf-8') as f:
        content = f.read()

    # Replace old :root completely
    content = re.sub(r':root\s*\{[^}]*\}', css_variables, content, count=1)
    
    # Remove dark-mode manual overrides block
    content = re.sub(r'body\.dark-mode[^{]*\{[^}]*\}(\s*body\.dark-mode[^{]*\{[^}]*\})*', '', content)
    content = re.sub(r'body\.dark-mode.*', '', content)
    
    # Replace colors in CSS
    replacements = [
        (r'var\(--bg-color\)', 'var(--background)'),
        (r'var\(--accent-color\)', 'var(--primary)'),
        (r'var\(--border-color\)', 'var(--border)'),
        (r'var\(--shadow-color\)', 'var(--border)'),
        
        # Hardcoded colors
        (r'background-color:\s*#FFD700', 'background-color: var(--primary)'),
        (r'background-color:\s*#fff', 'background-color: var(--card)'),
        (r'background-color:\s*#ffffff', 'background-color: var(--card)'),
        (r'background-color:\s*#F4F4F0', 'background-color: var(--background)'),
        (r'background-color:\s*#FF5252', 'background-color: var(--primary)'),
        (r'background-color:\s*#FF4C4C', 'background-color: var(--primary)'),
        (r'background-color:\s*#000', 'background-color: var(--foreground)'),
        (r'background-color:\s*#222', 'background-color: var(--muted)'),
        (r'background-color:\s*#f0f0e8', 'background-color: var(--muted)'),
        
        (r'color:\s*#000', 'color: var(--foreground)'),
        (r'color:\s*#fff', 'color: var(--primary-foreground)'), # context dependent, but usually inside buttons/dark elements
        (r'color:\s*#444', 'color: var(--muted)'),
        (r'color:\s*#555', 'color: var(--muted)'),
        (r'color:\s*#666', 'color: var(--muted)'),
        
        (r'border([^:]*):\s*([^;]*)(#000|#000000)', r'border\1: \2var(--border)'),
        (r'border([^:]*):\s*([^;]*)(#fff|#ffffff)', r'border\1: \2var(--card)'),
        
        (r'box-shadow:\s*([^#;]+)(#000|#000000)', r'box-shadow: \1var(--border)'),
        
        (r'border-radius:\s*0', 'border-radius: var(--radius)'),
    ]
    
    for old, new in replacements:
        content = re.sub(old, new, content, flags=re.IGNORECASE)
        
    with open('style.css', 'w', encoding='utf-8') as f:
        f.write(content)

def update_php_files():
    php_files = glob.glob('*.php')
    
    inline_replacements = [
        (r'var\(--accent-color\)', 'var(--primary)'),
        (r'#A8E6CF', 'var(--card)'),
        (r'#FFD3B6', 'var(--card)'),
        (r'#DCEDC1', 'var(--card)'),
        (r'#FFD700', 'var(--primary)'),
        (r'#FF5252', 'var(--primary)'),
        (r'#FF4C4C', 'var(--primary)'),
        (r'#fff', 'var(--card)'),
        (r'#ffffff', 'var(--card)'),
        (r'#000\b', 'var(--border)'), # naive, but inline #000 is mostly border/text. For color, we should use foreground.
    ]
    
    for f in php_files:
        with open(f, 'r', encoding='utf-8') as file:
            content = file.read()
            
        original_content = content
        
        # specific fix for color: #000 in inline styles
        content = re.sub(r'color:\s*#000', 'color: var(--foreground)', content, flags=re.IGNORECASE)
        content = re.sub(r'color:\s*#fff', 'color: var(--primary-foreground)', content, flags=re.IGNORECASE)
        content = re.sub(r'color:\s*#ffffff', 'color: var(--primary-foreground)', content, flags=re.IGNORECASE)
        content = re.sub(r'border:\s*([^;]*?)#000', r'border: \1var(--border)', content, flags=re.IGNORECASE)
        content = re.sub(r'border-bottom:\s*([^;]*?)#000', r'border-bottom: \1var(--border)', content, flags=re.IGNORECASE)
        content = re.sub(r'box-shadow:\s*([^;]*?)#000', r'box-shadow: \1var(--border)', content, flags=re.IGNORECASE)
        content = re.sub(r'background-color:\s*#000', 'background-color: var(--foreground)', content, flags=re.IGNORECASE)
        
        for old, new in inline_replacements:
            content = re.sub(old, new, content, flags=re.IGNORECASE)
            
        # Update dark mode script logic in navbar.php
        if f == 'navbar.php':
            content = content.replace('document.body.classList.toggle("dark-mode");', 'document.body.classList.toggle("dark");')
            content = content.replace('document.body.classList.contains("dark-mode")', 'document.body.classList.contains("dark")')
            content = content.replace('document.body.classList.add("dark-mode");', 'document.body.classList.add("dark");')
            
        if content != original_content:
            with open(f, 'w', encoding='utf-8') as file:
                file.write(content)
            print(f'Updated {f}')

update_css()
update_php_files()
print('Refactoring complete.')
