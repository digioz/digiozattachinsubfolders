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

## Compatibility

- phpBB 3.3.15+
- PHP 8.1+

## License

GPL-2.0-only