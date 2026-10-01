import json
import os

sql_file = r'D:\Magang SISfo\backup_db_simrs_masking-202609101406.sql'
out_dir = r'D:\Magang SISfo\bigsirs\BigSIRS\backend\storage\app\lookups'
os.makedirs(out_dir, exist_ok=True)

tables = {
    'ref_provinsi': [],
    'ref_kabupaten': [],
    'ref_kecamatan': [],
    'ref_kelurahan': [],
    'ref_pendidikan': [],
    'ref_pekerjaan': []
}

current_table = None

with open(sql_file, 'r', encoding='utf8', errors='ignore') as f:
    for line in f:
        if line.startswith('INSERT INTO `'):
            t = line.split('`')[1]
            if t in tables:
                # parse values
                # INSERT INTO `t` VALUES (...),(...);
                values_str = line[line.find('VALUES ')+7:].strip()
                if values_str.endswith(';'):
                    values_str = values_str[:-1]
                # split by '),'
                tuples = values_str.split('),(')
                for tup in tuples:
                    tup = tup.strip('()')
                    parts = [p.strip().strip("'") for p in tup.split(',')]
                    tables[t].append(parts)

for t, data in tables.items():
    res = []
    if t == 'ref_provinsi':
        for row in data:
            if len(row) >= 2:
                res.append({'id': row[0], 'name': row[1]})
    elif t == 'ref_kabupaten':
        for row in data:
            if len(row) >= 3:
                res.append({'id': row[0], 'province_id': row[1], 'name': row[2]})
    elif t == 'ref_kecamatan':
        for row in data:
            if len(row) >= 3:
                res.append({'id': row[0], 'city_id': row[1], 'name': row[2]})
    elif t == 'ref_kelurahan':
        for row in data:
            if len(row) >= 3:
                res.append({'id': row[0], 'district_id': row[1], 'name': row[2]})
    elif t == 'ref_pendidikan':
        for row in data:
            if len(row) >= 2:
                res.append({'id': row[0], 'name': row[1]})
    elif t == 'ref_pekerjaan':
        for row in data:
            if len(row) >= 2:
                res.append({'id': row[0], 'name': row[1]})
    
    with open(os.path.join(out_dir, f"{t}.json"), 'w') as f:
        json.dump(res, f)

print("Extracted to JSON")
