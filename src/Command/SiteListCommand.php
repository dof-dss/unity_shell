<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Yaml\Yaml;

#[AsCommand(
    name: 'site:list',
    description: 'Display a list of sites for this project',
    hidden: false,
    aliases: ['sa']
)]
class SiteListCommand extends Command {

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);
        $rows = [];

        $project = Yaml::parseFile(getcwd() . '/project/project.yml');

        foreach ($project['sites'] as $site) {
            $rows[] = [$site['name'], $site['url'], $site['database'], (empty($site['solr'])) ? 'No' : 'Yes', ($site['deploy']) ? 'Yes' : 'No'];
        }


        $table = new Table($output);
        $table->setHeaderTitle($project['application_name'] . ' (' . $project['application_id'] . ')');
        $table->setHeaders(['Name', 'URL', 'Database', 'Solr', 'Deployed'])
            ->setRows($rows);
        $table->render();

        return Command::SUCCESS;

    }
}
