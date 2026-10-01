import glob, os

for f in glob.glob("database/migrations/*_create_education_table.php"):
    content = open(f).read().replace("Schema::create('education',", "Schema::create('educations',")
    content = content.replace("Schema::dropIfExists('education');", "Schema::dropIfExists('educations');")
    open(f, "w").write(content.replace("$table->id();", "$table->id();\n            $table->string('legacy_id')->nullable()->unique();\n            $table->string('name');"))

for f in glob.glob("database/migrations/*_create_occupations_table.php"):
    content = open(f).read().replace("$table->id();", "$table->id();\n            $table->string('legacy_id')->nullable()->unique();\n            $table->string('name');")
    open(f, "w").write(content)

for f in glob.glob("database/migrations/*_create_provinces_table.php"):
    content = open(f).read().replace("$table->id();", "$table->id();\n            $table->string('legacy_id')->nullable()->unique();\n            $table->string('name');")
    open(f, "w").write(content)

for f in glob.glob("database/migrations/*_create_cities_table.php"):
    content = open(f).read().replace("$table->id();", "$table->id();\n            $table->string('legacy_id')->nullable()->unique();\n            $table->string('name');\n            $table->foreignId('province_id')->constrained()->onDelete('cascade');")
    open(f, "w").write(content)

for f in glob.glob("database/migrations/*_create_districts_table.php"):
    content = open(f).read().replace("$table->id();", "$table->id();\n            $table->string('legacy_id')->nullable()->unique();\n            $table->string('name');\n            $table->foreignId('city_id')->constrained()->onDelete('cascade');")
    open(f, "w").write(content)

for f in glob.glob("database/migrations/*_create_villages_table.php"):
    content = open(f).read().replace("$table->id();", "$table->id();\n            $table->string('legacy_id')->nullable()->unique();\n            $table->string('name');\n            $table->foreignId('district_id')->constrained()->onDelete('cascade');")
    open(f, "w").write(content)

print("Done")
