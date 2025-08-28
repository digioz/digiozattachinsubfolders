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
            'core.download_file_send_to_browser_before' => 'on_download_file_send_to_browser_before',
        ];
    }

    public function on_submit_post_end($event)
    {
        $this->organize_attachments();
    }

    /**
     * Restore subfoldered physical_filename before send_file_to_browser() is called.
     * Handle thumbnails by prefixing the basename and disabling later processing.
     *
     * @param array $event
     */
    public function on_download_file_send_to_browser_before($event)
    {
        global $db;

        if (empty($event['attachment']) || !is_array($event['attachment'])) {
            return;
        }

        $attach_id = !empty($event['attach_id'])
            ? (int) $event['attach_id']
            : (int) ($event['attachment']['attach_id'] ?? 0);

        if ($attach_id <= 0) {
            return;
        }

        $is_thumbnail = !empty($event['thumbnail']);

        $sql = 'SELECT physical_filename FROM ' . ATTACHMENTS_TABLE . ' WHERE attach_id = ' . $attach_id;
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
        $db->sql_freeresult($result);

        if (!$row || empty($row['physical_filename'])) {
            return;
        }

        $physical = $row['physical_filename'];

        if (strpos($physical, '/') !== false) {
            if ($is_thumbnail) {
                $parts = pathinfo($physical);
                $physical = $parts['dirname'] . '/thumb_' . $parts['basename'];
                $event['thumbnail'] = false;
            }

            $attachment = $event['attachment'];
            $attachment['physical_filename'] = $physical;
            $event['attachment'] = $attachment;
        }
    }

    private function organize_attachments()
    {
        global $db;

        $upload_path = rtrim($this->phpbb_root_path, '/\\') . '/' . trim($this->config['upload_path'], '/\\') . '/';

        if (!is_dir($upload_path)) {
            return;
        }

        $files = scandir($upload_path);
        foreach ($files as $file) {
            if (preg_match('/^\d+_[a-f0-9]{32}$/', $file)) {
                $source = $upload_path . $file;

                $md5 = md5($file);
                $subfolder = substr($md5, 0, 2) . '/' . substr($md5, 2, 2) . '/';
                $target_dir = $upload_path . $subfolder;
                $target = $target_dir . $file;
                $new_path = $subfolder . $file;

                if (!file_exists($target)) {
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0755, true);
                    }

                    if (@rename($source, $target)) {
                        $thumb_source = $upload_path . 'thumb_' . $file;
                        $thumb_target = $target_dir . 'thumb_' . $file;
                        if (file_exists($thumb_source)) {
                            @rename($thumb_source, $thumb_target);
                        }

                        $sql = 'UPDATE ' . ATTACHMENTS_TABLE . '
                            SET physical_filename = \'' . $db->sql_escape($new_path) . '\'
                            WHERE physical_filename = \'' . $db->sql_escape($file) . '\'';
                        $db->sql_query($sql);
                    }
                }
            }
        }

        $sql = 'SELECT a.attach_id, a.poster_id, a.filetime
            FROM ' . ATTACHMENTS_TABLE . ' a
            WHERE (a.post_msg_id = 0 OR a.topic_id = 0) AND a.is_orphan = 0';
        $result = $db->sql_query($sql);

        while ($row = $db->sql_fetchrow($result)) {
            $post_sql = 'SELECT post_id, topic_id
                FROM ' . POSTS_TABLE . '
                WHERE poster_id = ' . (int) $row['poster_id'] . '
                AND ABS(post_time - ' . (int) $row['filetime'] . ') < 1800
                ORDER BY ABS(post_time - ' . (int) $row['filetime'] . ') ASC
                LIMIT 1';
            $post_result = $db->sql_query($post_sql);
            $post_row = $db->sql_fetchrow($post_result);

            if ($post_row) {
                $update_sql = 'UPDATE ' . ATTACHMENTS_TABLE . '
                    SET post_msg_id = ' . (int) $post_row['post_id'] . ',
                        topic_id = ' . (int) $post_row['topic_id'] . '
                    WHERE attach_id = ' . (int) $row['attach_id'];
                $db->sql_query($update_sql);
            }

            $db->sql_freeresult($post_result);
        }

        $db->sql_freeresult($result);
    }
}