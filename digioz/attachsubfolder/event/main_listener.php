<?php
namespace digioz\attachsubfolder\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class main_listener implements EventSubscriberInterface
{
    protected $phpbb_root_path;
    protected $config;
    protected $filesystem;

    public function __construct($phpbb_root_path, $config, $filesystem)
    {
        $this->phpbb_root_path = $phpbb_root_path;
        $this->config = $config;
        $this->filesystem = $filesystem;
    }

    public static function getSubscribedEvents()
    {
        return [
            'core.submit_post_end' => 'on_submit_post_end',
            'core.modify_submit_post_data' => 'on_modify_submit_post_data',
            'core.send_file_to_browser_before' => 'on_send_file_to_browser_before',
        ];
    }

    public function on_submit_post_end($event)
    {
        // This happens AFTER the post is created, so we have post_id and topic_id
        if (empty($event['data']['attachment_data'])) {
            return;
        }

        $post_id = $event['data']['post_id'];
        $topic_id = $event['data']['topic_id'];
        $upload_path = $this->phpbb_root_path . $this->config['upload_path'] . '/';
        
        // Debug logging to see what we have
        if (!empty($this->config['debug'])) {
            error_log("submit_post_end: post_id=$post_id, topic_id=$topic_id");
        }
        
        foreach ($event['data']['attachment_data'] as $attachment) {
            if (empty($attachment['physical_filename']) || !empty($attachment['is_orphan'])) {
                continue;
            }

            // Move to subfolder and ensure post/topic IDs are correct
            $this->move_and_update_attachment($upload_path, $attachment, $post_id, $topic_id);
        }
    }

    public function on_modify_submit_post_data($event)
    {
        // This might be called before the post is created
    }

    public function on_send_file_to_browser_before($event)
    {
        // Handle file serving for files in subfolders
        $attachment = $event['attachment'];
        $upload_dir = $event['upload_dir'];
        $filename = $event['filename'];
        
        if (!file_exists($filename)) {
            $physical_filename = $attachment['physical_filename'];
            
            // Check if it's already a subfolder path
            if (strpos($physical_filename, '/') !== false) {
                // Already has subfolder path, just use it
                $subfolder_file = $this->phpbb_root_path . $upload_dir . '/' . $physical_filename;
            } else {
                // Calculate subfolder path
                $base_filename = $physical_filename;
                if (strpos($physical_filename, 'thumb_') === 0) {
                    $base_filename = substr($physical_filename, 6);
                }
                
                $md5 = md5($base_filename);
                $subfolder = substr($md5, 0, 2) . '/' . substr($md5, 2, 2) . '/';
                $subfolder_file = $this->phpbb_root_path . $upload_dir . '/' . $subfolder . $physical_filename;
            }
            
            if (file_exists($subfolder_file)) {
                $event['filename'] = $subfolder_file;
                if (isset($event['size'])) {
                    $event['size'] = filesize($subfolder_file);
                }
            }
        }
    }

    private function move_and_update_attachment($upload_path, $attachment, $post_id, $topic_id)
    {
        global $db;
        
        $physical_filename = $attachment['physical_filename'];
        $attach_id = $attachment['attach_id'];
        $source_file = $upload_path . $physical_filename;
        
        if (!file_exists($source_file)) {
            return;
        }

        $subfolder_path = $this->get_subfolder_path($physical_filename);
        $target_dir = $upload_path . $subfolder_path;
        $target_file = $target_dir . $physical_filename;
        $new_physical_filename = $subfolder_path . $physical_filename;
        
        try {
            // Create target directory
            if (!is_dir($target_dir)) {
                $this->filesystem->mkdir($target_dir, 0755);
            }
            
            // Move the main file
            if (rename($source_file, $target_file)) {
                // Move thumbnail if it exists
                $thumb_source = $upload_path . 'thumb_' . $physical_filename;
                $thumb_target = $target_dir . 'thumb_' . $physical_filename;
                
                if (file_exists($thumb_source)) {
                    rename($thumb_source, $thumb_target);
                }
                
                // CRITICAL: Update the database with correct post/topic IDs AND new path
                $sql = 'UPDATE ' . ATTACHMENTS_TABLE . ' 
                        SET physical_filename = \'' . $db->sql_escape($new_physical_filename) . '\',
                            post_msg_id = ' . (int) $post_id . ',
                            topic_id = ' . (int) $topic_id . '
                        WHERE attach_id = ' . (int) $attach_id;
                $db->sql_query($sql);
                
                if (!empty($this->config['debug'])) {
                    error_log("Updated attachment $attach_id: post_id=$post_id, topic_id=$topic_id, path=$new_physical_filename");
                }
            }
        } catch (\Exception $e) {
            error_log('Failed to move attachment: ' . $e->getMessage());
        }
    }

    private function get_subfolder_path($physical_filename)
    {
        $md5 = md5($physical_filename);
        $folder1 = substr($md5, 0, 2);
        $folder2 = substr($md5, 2, 2);
        return $folder1 . '/' . $folder2 . '/';
    }
}
