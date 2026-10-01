import re

with open(r'D:\Magang SISfo\backup_db_simrs_masking-202609101406.sql', 'r', encoding='utf8', errors='ignore') as f:
    for line in f:
        if line.startswith('CREATE TABLE `'):
            t = line.split('`')[1]
            if 'prov' in t.lower() or 'kab' in t.lower() or 'kec' in t.lower() or 'kel' in t.lower() or 'pendid' in t.lower() or 'pekerj' in t.lower():
                print(t)
