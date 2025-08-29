# Attachment Subfolder Storage Extension

This phpBB extension automatically organizes attachment files into subfolders based on the MD5 hash of their physical filename.

## Features

- Automatically moves uploaded attachments into a two-level subfolder structure
- Subfolder structure: `upload_folder/XX/YY/filename` where XX and YY are the first 4 characters of the MD5 hash
- Handles downloads automatically by looking for files in the correct subfolder
- Includes migration to move existing attachments when the extension is enabled
- Compatible with phpBB 3.3.15+

## Installation

1. Copy the extension files to `ext/digioz/attachsubfolder/`
2. Go to ACP > Customise > Manage extensions
3. Enable "Attachment Subfolder Storage"
4. The extension will automatically migrate existing attachments to subfolders

## How it works

When a post with attachments is submitted, the extension:
1. Calculates the MD5 hash of each attachment's physical filename
2. Creates a subfolder structure using the first 4 characters of the hash (XX/YY)
3. Moves the attachment file from the upload root to the subfolder

When downloading an attachment, the extension:
1. Checks if the file exists in the subfolder structure
2. If found, updates the file path to include the subfolder
3. Allows normal download to proceed

## Console Commands

### attachsubfolder:move

Bulk migrate existing attachments from the upload root directory to organized subfolders.

#### Prerequisites
- Extension must be installed and enabled via ACP
- Command line access to your phpBB installation directory
- PHP CLI available

#### Usage
```
# Navigate to your phpBB root directory
cd /path/to/phpbb

# Run the migration command
php bin/phpbbcli.php attachsubfolder:move
```

#### What it does
- Scans the upload directory for attachment files (pattern: `\d+_[a-f0-9]{32}`)
- Skips directories, `.htaccess`, `index.htm`, and thumbnail files
- For each file:
  - Calculates MD5 hash of the filename
  - Creates subfolder structure: `XX/YY/` (first 4 chars of MD5)
  - Moves file to the subfolder
  - Moves corresponding thumbnail if it exists
- Provides progress output and completion summary

#### Example Output
```
Moving Attachments to Subfolders
================================

Processing: 12345_abcdef1234567890abcdef1234567890
  ✓ Moved to subfolder

Processing: 67890_fedcba0987654321fedcba0987654321
  ✓ Moved to subfolder

[OK] Completed: 25 files moved, 0 errors
```

#### Important Notes
- **Database Update**: The console command only moves files but doesn't update database `physical_filename` values. The extension handles this via event listeners during downloads.
- **Safe to Re-run**: The command checks if files already exist in target locations and won't overwrite.
- **Backup First**: Always backup your attachments directory before running bulk operations.

## Compatibility

- phpBB 3.3.15+
- PHP 8.1+

## License

GPL-2.0-only