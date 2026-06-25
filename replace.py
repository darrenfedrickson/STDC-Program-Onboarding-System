import os
import re

folder = '/Applications/XAMPP/xamppfiles/htdocs/STDC-Program-Onboarding-System'

for root, dirs, files in os.walk(folder):
    if '.git' in root or '.gemini' in root: continue
    for file in files:
        if file.endswith(('.php', '.js', '.md', '.json')):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            orig_content = content

            # Clean up the previous bad python replacement
            content = content.replace("window.BASE_URL + \\'/", "window.BASE_URL + '/")
            content = content.replace("\\'", "'")

            # Handle JS template literal fetch
            content = re.sub(r'`/STDC-Program-Onboarding-System/(.*?)`', r'`${window.BASE_URL}/\1`', content)
            
            # Re-run normal JS string fetch if not already done
            content = re.sub(r'fetch\(\'/STDC-Program-Onboarding-System/(.*?)\'', r"fetch(window.BASE_URL + '/\1'", content)
            content = re.sub(r'fetch\(\"/STDC-Program-Onboarding-System/(.*?)\"', r'fetch(window.BASE_URL + "/\1"', content)

            if file.endswith('.php'):
                # PHP block logic
                content = re.sub(r'isActive\(\'/STDC-Program-Onboarding-System(.*?)\'', r"isActive(BASE_URL . '\1'", content)
                content = re.sub(r'\$path === \'/STDC-Program-Onboarding-System(.*?)\'', r"$path === BASE_URL . '\1'", content)
                content = re.sub(r'strpos\(\$currentPath, \'/STDC-Program-Onboarding-System(.*?)\'', r"strpos($currentPath, BASE_URL . '\1'", content)
                content = re.sub(r'\'/STDC-Program-Onboarding-System/\'', r"BASE_URL . '/'", content)

                # HTML attributes
                content = re.sub(r'href="/STDC-Program-Onboarding-System(.*?)"', r'href="<?php echo BASE_URL; ?>\1"', content)
                content = re.sub(r'src="/STDC-Program-Onboarding-System(.*?)"', r'src="<?php echo BASE_URL; ?>\1"', content)
                content = re.sub(r'action="/STDC-Program-Onboarding-System(.*?)"', r'action="<?php echo BASE_URL; ?>\1"', content)

            # Re-run markdown/json
            if file.endswith('.md') or file.endswith('.json'):
                content = content.replace('/STDC-Program-Onboarding-System', '/<BASE_URL>')
            
            if content != orig_content:
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f'Updated {file}')
