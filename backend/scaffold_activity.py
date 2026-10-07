import os
import re

files_to_copy = [
    ('app/Models/DietType.php', 'app/Models/ActivityType.php'),
    ('app/Http/Controllers/Api/V1/MasterData/DietTypeController.php', 'app/Http/Controllers/Api/V1/MasterData/ActivityTypeController.php'),
    ('app/Services/MasterData/DietTypeService.php', 'app/Services/MasterData/ActivityTypeService.php'),
    ('app/Http/Resources/Api/V1/MasterData/DietTypeResource.php', 'app/Http/Resources/Api/V1/MasterData/ActivityTypeResource.php'),
    ('app/Http/Requests/Api/V1/MasterData/StoreDietTypeRequest.php', 'app/Http/Requests/Api/V1/MasterData/StoreActivityTypeRequest.php'),
    ('app/Http/Requests/Api/V1/MasterData/UpdateDietTypeRequest.php', 'app/Http/Requests/Api/V1/MasterData/UpdateActivityTypeRequest.php'),
    ('app/Policies/DietTypePolicy.php', 'app/Policies/ActivityTypePolicy.php'),
    ('database/factories/DietTypeFactory.php', 'database/factories/ActivityTypeFactory.php'),
    ('tests/Feature/MasterData/DietTypeTest.php', 'tests/Feature/MasterData/ActivityTypeTest.php'),
]

for src, dst in files_to_copy:
    with open(src, 'r', encoding='utf-8') as f:
        content = f.read()
    
    content = content.replace('DietType', 'ActivityType')
    content = content.replace('dietType', 'activityType')
    content = content.replace('diet-types', 'activity-types')
    content = content.replace('diet_types', 'activity_types')
    content = content.replace('diet_type', 'activity_type')
    content = content.replace('Diet Type', 'Activity Type')
    content = content.replace('Asuhan Gizi', 'Jenis Kegiatan')
    content = content.replace('asuhan gizi', 'jenis kegiatan')
    
    os.makedirs(os.path.dirname(dst), exist_ok=True)
    with open(dst, 'w', encoding='utf-8') as f:
        f.write(content)

print("Scaffold complete.")
