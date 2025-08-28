<?php
namespace digioz\attachsubfolder\console\command;

use phpbb\console\command\command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class move_attachments extends command
{
    /** @var \phpbb\config\config */
    protected $config;

    /** @var \phpbb\db\driver\driver_interface */
    protected $db;

    /** @var \phpbb\filesystem\filesystem_interface */
    protected $filesystem;

    /** @var string */
    protected $phpbb_root_path;

    public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\filesystem\filesystem_interface $filesystem, $phpbb_root_path)
    {
        $this->config = $config;
        $this->db = $db;
        $this->filesystem = $filesystem;
        $this->phpbb_root_path = $phpbb_root_path;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('attachsubfolder:move')
            ->setDescription('Move attachments from root to subfolders');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        
        $upload_path = $this->phpbb_root_path . $this->config['upload_path'] . '/';
        
        $io->title('Moving Attachments to Subfolders');
        
        // Get all files in the upload directory (non-recursively)
        $files = glob($upload_path . '*', GLOB_MARK);
        $moved = 0;
        $errors = 0;
        
        foreach ($files as $file) {
            // Skip directories and special files
            if (is_dir($file) || basename($file) === '.htaccess' || basename($file) === 'index.htm') {
                continue;
            }
            
            $filename = basename($file);
            
            // Skip thumbnail files - they'll be moved with their main files
            if (strpos($filename, 'thumb_') === 0) {
                continue;
            }
            
            $io->writeln("Processing: $filename");
            
            if ($this->move_file_to_subfolder($upload_path, $filename)) {
                $moved++;
                $io->writeln("  ? Moved to subfolder");
            } else {
                $errors++;
                $io->writeln("  ? Failed to move");
            }
        }
        
        $io->success("Completed: $moved files moved, $errors errors");
        
        return 0;
    }

    private function move_file_to_subfolder($upload_path, $physical_filename)
    {
        $old_path = $upload_path . $physical_filename;
        
        if (!file_exists($old_path)) {
            return false;
        }

        $subfolder_path = $this->get_subfolder_path($physical_filename);
        $target_dir = $upload_path . $subfolder_path;
        $new_path = $target_dir . $physical_filename;
        
        // Don't move if it's already in the right place
        if ($old_path === $new_path) {
            return true;
        }

        try {
            // Create the target directory structure
            $this->filesystem->mkdir($target_dir, 0755);
            
            // Move the file to the subfolder
            if (!file_exists($new_path)) {
                if (rename($old_path, $new_path)) {
                    // Also move thumbnail if it exists
                    $thumb_old_path = $upload_path . 'thumb_' . $physical_filename;
                    $thumb_new_path = $target_dir . 'thumb_' . $physical_filename;
                    if (file_exists($thumb_old_path)) {
                        rename($thumb_old_path, $thumb_new_path);
                    }
                    return true;
                }
            }
        } catch (\Exception $e) {
            return false;
        }
        
        return false;
    }

    private function get_subfolder_path($physical_filename)
    {
        // Create MD5 hash-based subfolder structure
        $md5 = md5($physical_filename);
        $folder1 = substr($md5, 0, 2);
        $folder2 = substr($md5, 2, 2);
        return $folder1 . '/' . $folder2 . '/';
    }
}