import glob

for f in glob.glob('*.php'):
    with open(f, 'r', encoding='utf-8') as file:
        content = file.read()
    if '</head>' in content and 'boxicons' not in content:
        content = content.replace('</head>', "<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>\n</head>")
        with open(f, 'w', encoding='utf-8') as file:
            file.write(content)
        print(f'Updated {f}')
