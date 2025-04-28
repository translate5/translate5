<?php
/*
 START LICENSE AND COPYRIGHT

 This file is part of translate5

 Copyright (c) 2013 - 2017 Marc Mittag; MittagQI - Quality Informatics;  All rights reserved.

 Contact:  http://www.MittagQI.com/  /  service (ATT) MittagQI.com

 This file may be used under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE version 3
 as published by the Free Software Foundation and appearing in the file agpl3-license.txt
 included in the packaging of this file.  Please review the following information
 to ensure the GNU AFFERO GENERAL PUBLIC LICENSE version 3 requirements will be met:
 http://www.gnu.org/licenses/agpl.html

 There is a plugin exception available for use with this release of translate5 for
 translate5: Please see http://www.translate5.net/plugin-exception.txt or
 plugin-exception.txt in the root folder of translate5.

 @copyright  Marc Mittag, MittagQI - Quality Informatics
 @author     MittagQI - Quality Informatics
 @license    GNU AFFERO GENERAL PUBLIC LICENSE version 3 with plugin-execption
 http://www.gnu.org/licenses/agpl.html http://www.translate5.net/plugin-exception.txt

 END LICENSE AND COPYRIGHT
 */

namespace Translate5\MaintenanceCli\Command;

use PDOException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Zend_Db;
use Zend_Exception;
use Zend_Registry;

class DatabaseQueryCommand extends Translate5AbstractCommand
{
    protected static $defaultName = 'database:query';

    protected function configure()
    {
        $this->setAliases(['db:query']);

        $this
            ->setDescription('Execute SQL query on the database')
            ->setHelp('Execute SQL query on the database and display the results')
            ->addArgument(
                'query',
                InputArgument::OPTIONAL,
                'The SQL query to execute'
            )
            ->addOption(
                'file',
                'f',
                InputOption::VALUE_REQUIRED,
                'Path to a file containing the SQL query'
            )
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_REQUIRED,
                'Limit the number of results',
                100
            )
            ->addOption(
                'raw',
                'r',
                InputOption::VALUE_NONE,
                'Display raw output (no table formatting)'
            );
    }

    /**
     * @throws Zend_Exception
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        // Get query from argument, file, or interactive input
        $query = $this->getQuery();
        if (empty($query)) {
            $this->output->writeln('<error>No query provided</error>');

            return self::FAILURE;
        }

        try {
            $dbConfig = Zend_Registry::get('config')->resources->db;
            $db = Zend_Db::factory($dbConfig);

            // Check if the query is a SELECT query or other type
            $isSelect = $this->isSelectQuery($query);

            if ($isSelect) {
                // For SELECT queries, fetch and display results
                $limit = (int) $this->input->getOption('limit');
                // Add LIMIT clause if not already present
                if ($limit > 0 && ! preg_match('/\bLIMIT\b/i', $query)) {
                    $query .= " LIMIT $limit";
                }

                $startTime = microtime(true);
                $stmt = $db->query($query);
                $results = $stmt->fetchAll();
                $executionTime = microtime(true) - $startTime;

                if (empty($results)) {
                    $this->output->writeln('<info>Query executed successfully. No results returned.</info>');
                } else {
                    if ($this->input->getOption('raw')) {
                        // Raw output
                        foreach ($results as $row) {
                            $this->output->writeln(print_r($row, true));
                        }
                    } else {
                        // Table output
                        $this->writeTable($results);
                    }

                    $this->output->writeln(sprintf(
                        '<info>Query returned %d %s in %.2f seconds</info>',
                        count($results),
                        count($results) === 1 ? 'row' : 'rows',
                        $executionTime
                    ));
                }
            } else {
                // TODO: should we allow this ?
                // For non-SELECT queries, execute and show affected rows
                $startTime = microtime(true);
                $affectedRows = $db->query($query);
                $executionTime = microtime(true) - $startTime;

                $this->output->writeln(sprintf(
                    '<info>Query executed successfully. %d %s affected in %.2f seconds</info>',
                    $affectedRows->rowCount(),
                    ($affectedRows->rowCount() === 1 ? 'row' : 'rows'),
                    $executionTime
                ));
            }

            return self::SUCCESS;
        } catch (PDOException $e) {
            $this->output->writeln('<error>Database error: ' . $e->getMessage() . '</error>');

            return self::FAILURE;
        } catch (\Exception $e) {
            $this->output->writeln('<error>Error: ' . $e->getMessage() . '</error>');

            return self::FAILURE;
        }
    }

    /**
     * Get the SQL query from argument, file, or interactive input
     */
    protected function getQuery(): string
    {
        // Check if query is provided as an argument
        $query = $this->input->getArgument('query');
        if (! empty($query)) {
            return $query;
        }

        // Check if query is provided in a file
        $filePath = $this->input->getOption('file');
        if (! empty($filePath)) {
            if (! file_exists($filePath)) {
                $this->output->writeln("<error>File not found: $filePath</error>");

                return '';
            }

            return file_get_contents($filePath);
        }

        // If no query provided, ask interactively
        $helper = $this->getHelper('question');
        $question = new Question('Enter SQL query (end with semicolon): ');
        $question->setMultiline(true);

        return $helper->ask($this->input, $this->output, $question);
    }

    /**
     * Check if the query is a SELECT query
     */
    protected function isSelectQuery(string $query): bool
    {
        $query = trim($query);

        return preg_match('/^SELECT\b/i', $query) ||
            preg_match('/^SHOW\b/i', $query) ||
            preg_match('/^DESCRIBE\b/i', $query) ||
            preg_match('/^EXPLAIN\b/i', $query);
    }
}
