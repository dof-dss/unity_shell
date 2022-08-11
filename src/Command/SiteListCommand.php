<?php

namespace App\Command;

use App\UnityShellCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

#[AsCommand(
    name: 'site:list',
    description: 'Display a list of sites for this project',
    hidden: false,
    aliases: ['sl']
)]
class SiteListCommand extends UnityShellCommand {

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $rows = [];

        // Unity2 Project file.
        $project = $this->fs()->readFile('/project/project.yml');

        foreach ($project['sites'] as $site) {
            $rows[] = [$site['name'], $site['url'], $site['database'], (empty($site['solr'])) ? 'No' : 'Yes', ($site['deploy']) ? 'Yes' : 'No'];
        }

        $table = new Table($output);
        $table->setHeaderTitle($project['project_name'] . ' (' . $project['project_id'] . ')');
        $table->setHeaders(['Name', 'URL', 'Database', 'Solr', 'Deployed'])
            ->setRows($rows);
        $table->render();

        return Command::SUCCESS;

    }
}
