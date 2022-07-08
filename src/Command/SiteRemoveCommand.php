<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(
    name: 'site:remove',
    description: 'Remove a site from the project',
    hidden: false,
    aliases: ['sa']
)]
class SiteRemoveCommand extends Command {

    protected function configure(): void {
        $this->addArgument('siteid', InputArgument::OPTIONAL, 'Site ID (Must be a machine name e.g. uregni)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $site_id = $input->getArgument('siteid');

        $project = Yaml::parseFile(getcwd() . '/project/project.yml');

        // Provide a list of sites from the project file for the user to select.
        if (empty($site_id)) {
            $site_options = ['Cancel'];
            $site_options = array_merge($site_options, array_keys($project['sites']));

            $helper = $this->getHelper('question');
            $sites_choice_list = new ChoiceQuestion(
                'Please select a site to remove',
               $site_options,
                0
            );
            $sites_choice_list->setErrorMessage('Site %s is invalid.');

            $site_id = $helper->ask($input, $output, $sites_choice_list);

            if ($site_id === 'Cancel') {
                $io->info('Cancelling site removal.');
                return Command::SUCCESS;
            }
        }

        if (array_key_exists($site_id, $project['sites'])) {
            unset($project['sites'][$site_id]);

            $project_config = Yaml::dump($project, 6);

            try {
                $filesystem->dumpFile(getcwd() . '/project/project.yml', $project_config);

                // Remove the site symlink. This should be done in the
                // project:build command but that would involve checking all
                // symlinks under /web/sites and removing those that don't match
                // a site id, not ideal so we remove it here.
                $filesystem->remove(getcwd() . '/web/sites/' . $site_id);

                $io->success('Successfully removed ' . $site_id . ' from the project.');
            }
            catch (IOExceptionInterface $exception) {
                $io->error('Unable to update Project file, error: ' . $exception->getMessage());
                return Command::FAILURE;
            }

            if($io->confirm('Would you like to rebuild the project?')) {
                $build_command = $this->getApplication()->find('project:build');

                $return_code = $build_command->run(new ArrayInput([]), $output);
                return $return_code;
            } else {
                $io->success('Successfully removed ' . $site_id . ' from the project.');
                return Command::SUCCESS;
            }
        } else {
            $io->error('Site ' . $site_id . ' not found within Projects file.');
            return Command::FAILURE;
        }
    }
}
