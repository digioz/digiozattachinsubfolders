<?php
/**
 *
 * DigiOz Attachments In Subfolders. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024, Pete Soheil, https://digioz.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace digioz\digiozattachinsubfolders\acp;

/**
 * DigiOz Attachments In Subfolders ACP module info.
 */
class main_info
{
	public function module()
	{
		return [
			'filename'	=> '\digioz\digiozattachinsubfolders\acp\main_module',
			'title'		=> 'ACP_DIGIOZATTACHINSUBFOLDERS_TITLE',
			'modes'		=> [
				'settings'	=> [
					'title'	=> 'ACP_DIGIOZATTACHINSUBFOLDERS',
					'auth'	=> 'ext_digioz/digiozattachinsubfolders && acl_a_board',
					'cat'	=> ['ACP_DIGIOZATTACHINSUBFOLDERS_TITLE'],
				],
			],
		];
	}
}
