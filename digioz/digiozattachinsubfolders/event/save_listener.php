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
use phpbb\files\attachment\manager;

/**
 * DigiOz Attachments In Subfolders Event listener.
 */
class save_listener implements EventSubscriberInterface
{
    public function __construct()
    {
        // Constructor can be empty if not using any service injection
    }

    public static function getSubscribedEvents()
    {
        return array(
            'core.modify_uploaded_file' => 'on_modify_uploaded_file',
        );
    }

    public function on_modify_uploaded_file($event)
    {
        // Get the uploaded file details from the event
        global $phpbb_root_path;

        // Get the attachment data from the event
        $attachment_data = $event['filedata'];

        $folder = explode('_', $attachment_data['physical_filename']);

        $md5_hash = $folder[1];
        // Use the first two characters of the hash to create a subfolder
        $subfolder_name = substr($md5_hash, 0, 2);
        $subfolder = $phpbb_root_path . 'files/' . $subfolder_name . '/';

        // Check if the subfolder exists, if not, create it
        if (!is_dir($subfolder)) {
            mkdir($subfolder, 0777, true);
        }

        // Define the destination for the file within the subfolder
        $destination = $subfolder . basename($attachment_data['physical_filename']);

        // Move the uploaded file to the subfolder
        if (isset($attachment_data['physical_filename']) && file_exists($phpbb_root_path . 'files/' . $attachment_data['physical_filename'])) {
            rename($phpbb_root_path . 'files/' . $attachment_data['physical_filename'], $destination);

            // Update the attachment data to reflect the new file path
            $attachment_data['physical_filename'] = $subfolder_name . '/' . basename($attachment_data['physical_filename']);
        }
    }
}
