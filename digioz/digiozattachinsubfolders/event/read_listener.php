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
        // Get the original file information
        $attachment = $event['attachment'];
        $physical_filename = $attachment['physical_filename'];

        // Find the position of the first underscore
        $underscore_pos = strpos($physical_filename, '_');
        $subfolder = '';

        if ($underscore_pos !== false && strlen($physical_filename) > $underscore_pos + 2) {
            // Extract the first 2 characters after the underscore as the subfolder
            $subfolder = substr($physical_filename, $underscore_pos + 1, 2);
            // Build the new full path with the subfolder
            $local_file = $subfolder . '/' . $physical_filename;
            if ($subfolder !== '') {
                $event['physical_filename'] = $local_file;
                return;
            }
        }

        // Fallback to the default path if underscore or enough characters are not found
        $event['physical_filename'] = $physical_filename;
    }
}
