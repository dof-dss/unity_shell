<?php

namespace App\Command;

use App\UnityShellCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(
    name: 'site:edit',
    description: 'Edit a site in the project',
    hidden: false,
    aliases: ['se']
)]
class SiteEditCommand extends UnityShellCommand {

    protected function configure(): void {
        $this->addArgument('siteid', InputArgument::OPTIONAL, 'Site ID (Must be a machine name e.g. uregni)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        $site_id = $input->getArgument('siteid');

        // Unity2 Project file.
        $project = $this->fs()->readFile('/project/project.yml');

        // Warn if we have no site entries.
        if (empty($project['sites'])) {
            $io->error('This project does not have any site definitions.');
            return Command::FAILURE;
        }

        // Provide a list of sites from the project file for the user to select.
        if (empty($site_id)) {
            $site_options = ['Cancel'];
            $site_options = array_merge($site_options, array_keys($project['sites']));

            $helper = $this->getHelper('question');
            $sites_choice_list = new ChoiceQuestion(
                'Please select a site to edit',
                $site_options,
                0
            );
            $sites_choice_list->setErrorMessage('Site %s is invalid.');

            $site_id = $helper->ask($input, $output, $sites_choice_list);

            if ($site_id === 'Cancel') {
                $io->info('Cancelling site edit.');
                return Command::SUCCESS;
            }
        }

        if (array_key_exists($site_id, $project['sites'])) {
            $site['name'] = $io->ask('Site name', $project['sites'][$site_id]['name']);
            $site['url'] = $io->ask('Site URL (minus the protocol and trailing slash', $project['sites'][$site_id]['url']);
            if ($io->confirm('Does this site require a Solr search?')) {
                $site['solr'] = $site_id;
            }

            $site['cron_spec'] = '10 * * * *';
            $site['cron_cmd'] = 'cd web/sites/' . $site_id . ' ; drush core-cron';

            $site['database'] = $site_id;
            $site['deploy'] = false;

            if ($io->confirm('Do you want this project deployed live on Platform?', array_key_exists('solr', $project['sites'][$site_id]))) {
                $site['deploy'] = true;
            }

            $project['sites'][$site_id] = $site;

            $project_config = Yaml::dump($project, 6);

            try {
                $this->fs()->dumpFile('/project/project.yml', $project_config);
                $io->success('Updated project file');

                $io->section('Site details for: ' . $site_id);
                foreach ($site as $property => $value) {
                    $io->writeln($property . ' : ' . $value);
                }

                if ($io->confirm('Would you like to rebuild the project?')) {
                    $build_command = $this->getApplication()->find('project:build');

                    $return_code = $build_command->run(new ArrayInput([]), $output);
                    return $return_code;
                }

                $io->success('Successfully added ' . $site_id . ' to the project.');
                return Command::SUCCESS;
            } catch (IOExceptionInterface $exception) {
                $io->error('Unable to update Project file, error: ' . $exception->getMessage());
                return Command::FAILURE;
            }
        }
        return Command::FAILURE;
    }
}
