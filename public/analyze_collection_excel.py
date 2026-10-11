import openpyxl
import json
import os
import sys

excel_path = r"D:\D\Automation-Innovation\scomm-collection\Updated Collection Status 30 Sep'26 Mehedi-.xlsx"

if not os.path.exists(excel_path):
    print(json.dumps({"error": f"File not found at {excel_path}"}))
    sys.exit(0)

wb = openpyxl.load_workbook(excel_path, data_only=True)
sheets = wb.sheetnames

target_sheet_name = None
for s in sheets:
    if 'total' in s.lower() and 'collection' in s.lower():
        target_sheet_name = s
        break
if not target_sheet_name:
    for s in sheets:
        if 'collection' in s.lower():
            target_sheet_name = s
            break

output = {
    "file_path": excel_path,
    "file_size": os.path.getsize(excel_path),
    "all_sheet_names": sheets,
    "target_sheet_name": target_sheet_name,
}

if target_sheet_name:
    ws = wb[target_sheet_name]
    output["max_row"] = ws.max_row
    output["max_column"] = ws.max_column

    # Read first 30 rows to find header and sample data
    rows_data = []
    for r in range(1, min(100, ws.max_row + 1)):
        row_vals = []
        for c in range(1, min(30, ws.max_column + 1)):
            v = ws.cell(r, c).value
            row_vals.append(str(v) if v is not None else "")
        if any(row_vals):
            rows_data.append({"row_num": r, "vals": row_vals})
    
    output["sample_rows"] = rows_data

with open(r"d:\laragon\www\scomm-collection\public\excel_analysis_result.json", "w", encoding="utf-8") as f:
    json.dump(output, f, indent=2)

print("SUCCESS")
