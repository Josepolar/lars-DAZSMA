# Installing PHPSpreadsheet for Excel Export

To enable .xlsx export functionality, you need to install the PHPSpreadsheet library using Composer.

## Installation Steps:

1. **Install Composer** (if not already installed):
   - Download from: https://getcomposer.org/download/
   - Run the installer for Windows

2. **Navigate to your project directory**:
   ```powershell
   cd c:\xampp\htdocs\larss
   ```

3. **Install PHPSpreadsheet**:
   ```powershell
   composer require phpoffice/phpspreadsheet
   ```

4. **Verify Installation**:
   - Check that a `vendor` folder was created in your project root
   - The folder should contain the PHPSpreadsheet library

## Alternative: CSV Export

If you don't want to install PHPSpreadsheet, the system will automatically fall back to CSV format export, which works without any additional libraries.

The CSV file can be opened in Excel and provides the same data, just in a simpler format.

## Testing the Bulk Export

1. Go to the Students page in the teacher dashboard
2. Click the "Bulk Export" button
3. Select a grade level
4. Click "Export"
5. The file will download automatically as either .xlsx (if PHPSpreadsheet is installed) or .csv (fallback)
