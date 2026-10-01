import re

with open(r'D:\Magang SISfo\backup_db_simrs_masking-202609101406.sql', 'r', encoding='utf8', errors='ignore') as f:
    in_table = False
    for line in f:
        if 'CREATE TABLE `master_person_index`' in line:
            in_table = True
        if in_table:
            print(line.strip())
            if line.strip().endswith(';'):
                break
