<?php

namespace digioz\attachsubfolder\migrations;

class move_existing_attachments extends \phpbb\db\migration\migration
{
    public function effectively_installed()
    {
        return isset($this->config['attachsubfolder_migration_complete']);
    }

    static public function depends_on()
    {
        return array('\phpbb\db\migration\data\v31x\v314');
    }

    public function update_data()
    {
        return array(
            array('custom', array(array($this, 'move_existing_files'))),
            array('config.add', array('attachsubfolder_migration_complete', 1)),
        );
    }

    public function move_existing_files()
    {
        $upload_path = $this->phpbb_root_path . $this->config['upload_path'] . '/';
        
        // Get all attachments from database
        $sql = 'SELECT physical_filename FROM ' . ATTACHMENTS_TABLE . ' WHERE is_orphan = 0';
        $result = $this->db->sql_query($sql);
        
        while ($row = $this->db->sql_fetchrow($result))
        {
            $physical_filename = $row['physical_filename'];
            $old_path = $upload_path . $physical_filename;
            
            // Only move if file exists in root upload directory
            if (!file_exists($old_path)) {
                continue;
            }
            
            // Create MD5 hash-based subfolder structure
            $md5 = md5($physical_filename);
            $folder1 = substr($md5, 0, 2);
            $folder2 = substr($md5, 2, 2);
            $target_dir = $upload_path . $folder1 . '/' . $folder2 . '/';
            $new_path = $target_dir . $physical_filename;
            
            // Don't move if it's already in the right place
            if ($old_path === $new_path) {
                continue;
            }
            
            try {
                // Create directory if it doesn't exist
                if (!is_dir($target_dir)) {
                    if (!mkdir($target_dir, 0755, true)) {
                        continue; // Skip if directory creation fails
                    }
                }
                
                // Move the file
                if (!file_exists($new_path)) {
                    rename($old_path, $new_path);
                }
            } catch (Exception $e) {
                // Log error but continue with other files
                error_log('Failed to move attachment: ' . $physical_filename . ' - ' . $e->getMessage());
            }
        }
        
        $this->db->sql_freeresult($result);
    }
}