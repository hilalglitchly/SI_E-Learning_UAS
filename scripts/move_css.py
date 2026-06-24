import os

with open('dashboard.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# find the <style> block under Pimpinan
start_idx = -1
end_idx = -1
for i, line in enumerate(lines):
    if '<!-- Internal Style khusus Pimpinan (Neo Brutalism) -->' in line:
        start_idx = i
    if start_idx != -1 and '</style>' in line and i > start_idx:
        end_idx = i
        break

if start_idx != -1 and end_idx != -1:
    # extract everything inside the style tags, plus the tags if we want, but we just want the inner CSS for style.css
    # lines[start_idx] is the comment. lines[start_idx+1] is <style>. lines[end_idx] is </style>.
    css_content = "".join(lines[start_idx+2:end_idx])
    
    # remove the <style> block from dashboard.php including the comment
    new_dashboard_lines = lines[:start_idx] + lines[end_idx+1:]
    with open('dashboard.php', 'w', encoding='utf-8') as f:
        f.writelines(new_dashboard_lines)
    
    # append to style.css
    with open('style.css', 'a', encoding='utf-8') as f:
        f.write("\n")
        f.write(css_content)
    
    print("CSS moved successfully.")
else:
    print("Could not find the style block.")
