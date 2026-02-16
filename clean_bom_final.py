import os

path = 'admin/toplanti-pdf.php'
# Read binary content
with open(path, 'rb') as f:
    data = f.read()

# Check for BOM
if data.startswith(b'\xef\xbb\xbf'):
    print("BOM detected. Removing...")
    clean_data = data[3:]
    with open(path, 'wb') as f:
        f.write(clean_data)
    print("BOM removed successfully.")
else:
    print("No BOM found.")

# Double check
with open(path, 'rb') as f:
    check = f.read(5)
print(f"File starts with: {check}")
