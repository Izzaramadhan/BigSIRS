import glob, os

for f in glob.glob("app/Models/*.php"):
    if f.endswith("Education.php") or f.endswith("Occupation.php") or f.endswith("Province.php") or f.endswith("City.php") or f.endswith("District.php") or f.endswith("Village.php"):
        content = open(f).read().replace("use HasFactory;", "use HasFactory;\n    protected $guarded = [];")
        open(f, "w").write(content)
print("Done")
