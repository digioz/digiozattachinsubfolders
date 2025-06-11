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

/**
 * DigiOz Attachments In Subfolders Event listener.
 */
class main_listener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'core.user_setup' => 'load_language_on_setup',
            'core.display_forums_modify_template_vars' => 'display_forums_modify_template_vars',
            'core.download_modify_attachment_path' => 'modify_attachment_path',
        ];
    }

	/* @var \phpbb\language\language */
    protected $language;

	/**
	 * Constructor
	 *
	 * @param \phpbb\language\language	$language	Language object
	 */
    public function __construct(\phpbb\language\language $language)
    {
        $this->language = $language;
    }

	/**
	 * Load common language files during user setup
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
    public function load_language_on_setup($event)
    {
        $lang_set_ext = $event['lang_set_ext'];
        $lang_set_ext[] = [
            'ext_name' => 'digioz/digiozattachinsubfolders',
            'lang_set' => 'common',
        ];
        $event['lang_set_ext'] = $lang_set_ext;
    }

    public function display_forums_modify_template_vars($event)
    {
        // Example: $forum_row = $event['forum_row'];
        // $forum_row['FORUM_NAME'] .= $this->language->lang('DIGIOZATTACHINSUBFOLDERS_EVENT');
        // $event['forum_row'] = $forum_row;

    }

    /**
     * Modify the attachment path to use subfolders.
     *
     * @param \phpbb\event\data $event
     */
    public function modify_attachment_path($event)
    {
        $physical_filename = $event['physical_filename'];

        // Find the position of the first underscore
        $underscore_pos = strpos($physical_filename, '_');
        $subfolder = '';

        if ($underscore_pos !== false && strlen($physical_filename) > $underscore_pos + 2) {
            // Extract the first 2 characters after the underscore
            $subfolder = substr($physical_filename, $underscore_pos + 1, 2);
            // Build the new full path with the subfolder
            $full_path_with_subfolder = $event['config']['upload_path'] . '/' . $subfolder . '/' . $physical_filename;
            //if ($subfolder !== '' && file_exists($full_path_with_subfolder)) {
            //    $event['full_path'] = $full_path_with_subfolder;
            //} else {
            //    $event['full_path'] = $event['config']['upload_path'] . '/' . $physical_filename;
            //}
            $event['full_path'] = $full_path_with_subfolder;
        } else {
            // Fallback to the default path if underscore or enough characters are not found
            $event['full_path'] = $event['config']['upload_path'] . '/' . $physical_filename;
        }
    }
}