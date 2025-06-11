<?php
/**
 *
 * DigiOz Attachments In Subfolders. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2024, Pete Soheil, https://digioz.com
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace digioz\digiozattachinsubfolders\migrations;

class install_acp_module extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['digioz_digiozattachinsubfolders_goodbye']);
	}

	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v320\v320'];
	}

	public function update_data()
	{
		return [
			['config.add', ['digioz_digiozattachinsubfolders_goodbye', 0]],

			['module.add', [
				'acp',
				'ACP_DIGIOZATTACHINSUBFOLDERS_TITLE',
				[
					'module_basename'	=> '\digioz\digiozattachinsubfolders\acp\main_module',
					'modes'				=> ['settings'],
				],
			]],
		];
	}
}
