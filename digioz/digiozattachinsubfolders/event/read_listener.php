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
    protected $root_path;

    public function __construct(path_helper $path_helper)
    {
        $this->root_path = $path_helper->get_phpbb_root_path();
    }

    public static function getSubscribedEvents()
    {
        return [
            'core.fileupload_before_read' => 'onFileRead',  // You'll need to hook into a specific event or use a custom one
        ];
    }

    public function onFileRead($event)
    {
        $filename = $event['filename']; // The filename to read

        // Generate MD5 hash of the filename
        $md5Hash = md5($filename);
        $subFolder = substr($md5Hash, 0, 2);
        $filePath = $this->root_path . 'files/' . $subFolder . '/' . $filename;

        if (file_exists($filePath)) {
            // Serve the file contents
            $response = new Response(file_get_contents($filePath), 200, [
                'Content-Type' => mime_content_type($filePath),
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);

            $response->send();  // Send the response
        } else {
            // Handle file not found
            throw new \Exception("File not found: $filename");
        }
    }
}
