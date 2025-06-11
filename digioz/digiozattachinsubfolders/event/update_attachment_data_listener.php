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
class update_attachment_data_listener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'core.modify_posting_parameters' => 'on_modify_posting_parameters',
        );
    }

    /**
     * Event handler for modify_posting_parameters
     */
    public function on_modify_posting_parameters($event)
    {
        // Retrieve the data passed to the event
        $post_data = $event['post_data'];
        $mode = $event['mode'];

        // Add a debug statement to check if the event is being triggered
        // trigger_error('Event "core.modify_posting_parameters" triggered!', E_USER_NOTICE);

        // Example: Modify attachment data (if applicable)
        if (isset($post_data['attachment_data']) && !empty($post_data['attachment_data'])) {
            foreach ($post_data['attachment_data'] as &$attachment) {
                // Example: Modify the attachment filename or add metadata
                $attachment['physical_filename'] = 'updated_filename';
                // You can also add custom logic to update other fields like post_msg_id, etc.
            }
        }

        // Update the event's data back into the event object
        $event['post_data'] = $post_data;
    }
}
