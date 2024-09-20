<?php
/**
 *
 * DigiOz Attachments In Subfolders. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024, Pete Soheil, https://digioz.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace digioz\digiozattachinsubfolders\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use phpbb\path_helper;
use Symfony\Component\HttpFoundation\Response;

class read_listener implements EventSubscriberInterface
{
    public function __construct()
    {

    }

    public static function getSubscribedEvents()
    {
        return array(
            'core.download_file_send_to_browser_before' => 'on_download_file',
        );
    }

    public function on_download_file($event)
    {
        global $phpbb_root_path;
        // Get the original file information
        $attachment = $event['attachment'];

        // Check if it's a file you're responsible for
        if ($attachment['extension'] !== 'your_extension_specific') {
            return;
        }
        
        $folder = explode('_', $attachment['physical_filename']);

        $md5_hash = $folder[1];
        // Use the first two characters of the hash to create a subfolder
        $subfolder_name = substr($md5_hash, 0, 2);
        $subfolder = $phpbb_root_path . 'files/' . $subfolder_name . '/';

        $full_file_path = $subfolder . $attachment['physical_filename'];

        // Check if the file exists in the custom subfolder
        if (file_exists($full_file_path) && is_readable($full_file_path)) {
            // Override the file path with the path to your custom subfolder
            $event['local_file'] = $full_file_path;
        } else {
            // Optionally, you can handle the case where the file is not found
            trigger_error('File not found in custom subfolder.', E_USER_WARNING);
        }
    }
}
