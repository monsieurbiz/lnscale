<?php
/**
 * @license See LICENSE file
 * @author Jacques Bodin-Hullin <j.bodinhullin@monsieurbiz.com> <@jacquesbh>
 * @copyright Copyright (c) 2017 Monsieur Biz (https://monsieurbiz.com/)
 */

namespace MonsieurBiz;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScaleCommand extends Command
{

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this
            ->setName('scale')
            ->setDescription('Scale symlinks')
            ->addArgument('service', InputArgument::REQUIRED, 'Service to scale. Should be a real directory.')
            ->addArgument('scale', InputArgument::REQUIRED, 'Number of symlinks. Should be an integer >= 0.')
        ;
    }

    /**
     * Execute the command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input The input
     * @param \Symfony\Component\Console\Output\OutputInterface $output The output
     *
     * @return null
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $service = $input->getArgument('service');
        $scale = (int) $input->getArgument('scale');

        if (!is_dir($service) || is_link($service)) {
            $output->writeln('<error>The service argument should be a real directory on the system.</error>');
            return 1;
        }

        if ($scale <= 0) {
            $output->writeln('<error>The scale argument should be greater or equal than 0.</error>');
            return 1;
        }

        $output->writeln(sprintf('<info>Scaling to %d…</info>', $scale));

        // Get info
        $realService = realpath($service);
        $info = pathinfo($service);

        // Grow up
        $mainDir = getcwd();
        for ($number = 1; $number <= $scale; $number++) {
            chdir($mainDir);

            // First dir
            if ($number === 1 && is_dir('./' . $info['basename'])) {
                continue;
            }

            $name = sprintf('%s___%d', $info['basename'], $number);
            if (!is_dir('./' . $name)) {
                mkdir('./' . $name);
                chdir($mainDir . '/' . $name);

                // Main run
                if (is_file($realService . '/run')) {
                    symlink($realService . '/run', './run');
                }

                // Logs
                if (is_dir($realService . '/log') && is_file($realService . '/log/run')) {
                    mkdir($mainDir . '/' . $name . '/log');
                    symlink($realService . '/log/run', './log/run');
                    if (is_file($realService . '/log/config')) {
                        symlink($realService . '/log/config', './log/config');
                    }
                }

                $output->writeln(sprintf('<comment>Scale %d -> %s</comment>', $number, $name));
            }
        }
        chdir($mainDir);

        // Decline
        $regexOfScaleDirectory = '`^(?P<name>[0-9a-z_.-]+)___(?P<number>[0-9]+)$`i';
        $dirs = glob(sprintf($mainDir . '/%s___*', $info['basename']));
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $dirInfo = pathinfo(realpath($dir));
                if (preg_match($regexOfScaleDirectory, $dirInfo['basename'], $matches)) {
                    if ((int) $matches['number'] > $scale) {
                        $this->_removeDir($output, $dir);
                        $output->writeln(sprintf('<comment>Remove %s</comment>', $dirInfo['basename']));
                    }
                }
            }
        }

        $output->writeln('<info>Done</info>');
    }

    /**
     * Remove directory recursively
     *
     * @param $output
     * @param $dir
     */
    protected function _removeDir($output, $dir)
    {
        if (is_dir($dir)) {
            $handle = opendir($dir);
            while (false !== ($entry = readdir($handle))) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $entry = $dir . '/' . $entry;
                if (is_file($entry) || is_link($entry)) {
                    unlink($entry);
                } else {
                    $this->_removeDir($output, $entry);
                }
            }
            closedir($handle);
            rmdir($dir);
        }
    }

}
