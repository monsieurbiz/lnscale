<?php

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
            ->addArgument('symlink', InputArgument::REQUIRED, 'Symlink to scale. Should be a symlink already.')
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
        $symlink = $input->getArgument('symlink');
        $scale = (int) $input->getArgument('scale');

        if (!is_link($symlink)) {
            $output->writeln('<error>The symlink argument should be a symlink on the system.</error>');
            return 1;
        }

        if ($scale <= 0) {
            $output->writeln('<error>The scale argument should be greater than 0.</error>');
            $output->writeln('<info>If you want to remove the first symlink, do it manually.</info>');
            return 1;
        }

        $output->writeln(sprintf('<info>Scaling to %d…</info>', $scale));

        // Get info
        $info = pathinfo($symlink);
        $link = realpath($symlink);

        // If the symlink is already a scale one, we get the name part
        $regexOfScaleLink = '`^(?P<name>[0-9a-z_]+)__(?P<number>[0-9]+)$`i';
        if (preg_match($regexOfScaleLink, $info['basename'], $matches)) {
            $info['basename'] = $matches['name'];
        }

//        // Change directory into the one of the symlink
//        $dir = realpath($info['dirname']);
//        chdir($dir);

        // Grow up
        for ($number = 1; $number <= $scale; $number++) {
            $name = ($number === 1) ? $info['basename'] : sprintf('%s__%d', $info['basename'], $number);
            if (!is_link('./' . $name)) {
                symlink($link, $name);
                $output->writeln(sprintf('<comment>Scale %d -> %s</comment>', $number, $name));
            }
        }

        // Decline
        $files = glob(sprintf('./%s__*', $info['basename']));
        foreach ($files as $file) {
            if (is_link($file)) {
                $fileInfo = pathinfo($file);
                if (preg_match($regexOfScaleLink, $fileInfo['basename'], $matches)) {
                    if ((int) $matches['number'] > $scale) {
                        unlink($file);
                        $output->writeln(sprintf('<comment>Remove %s</comment>', $fileInfo['basename']));
                    }
                }
            }
        }

        $output->writeln('<info>Done</info>');
    }

}
