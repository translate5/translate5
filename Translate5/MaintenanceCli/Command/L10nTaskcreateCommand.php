<?php

namespace Translate5\MaintenanceCli\Command;

use editor_Models_Customer_Customer;
use editor_Models_Import_CliImportWorker;
use ReflectionException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Translate5\MaintenanceCli\L10n\L10nHelper;
use Zend_Db_Statement_Exception;
use Zend_Exception;
use ZfExtended_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_Exceptions_IntegrityConstraint;
use ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User as User;
use ZfExtended_Utils;

class L10nTaskcreateCommand extends Translate5AbstractCommand
{
    public const LOCALE = 'locale';

    public const PM_LOGIN = 'pm-login';

    public const CUSTOMER = 'customer';

    protected static $defaultName = 'l10n:taskcreate';

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Pushes the localizations of the current instance into a translation/review task.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Converts the texts of the current instance into a translation/review task. ' .
                'JSON files in the code will be converted toi bilingual XLIFF imports based on the primary locale.'
            );

        $this->addArgument(
            self::LOCALE,
            InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
            'The locale(s) for which the task should be created. If not given, tasks for all locales are created'
        );

        $this->addOption(
            self::PM_LOGIN,
            'p',
            InputOption::VALUE_REQUIRED,
            'The login of the pm associated to the task. If omitted, the systemuser is used ' .
                'and the PM must be changed afterwards'
        );

        $this->addOption(
            self::CUSTOMER,
            'c',
            InputOption::VALUE_REQUIRED,
            'The customer number of the customer to be used. Defaults to “defaultcustomer”.'
        );
    }

    /**
     * @throws ZfExtended_Models_Entity_NotFoundException
     * @throws Zend_Exception
     * @throws ReflectionException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initInputOutput($input, $output);
        $this->initTranslate5();

        $this->writeTitle('Translate5 L10n maintenance - create a translation package as translate5 task');

        $allLocales = L10nHelper::getAllLocales();
        $locales = $this->input->getArgument(self::LOCALE);
        if (! empty($locales)) {
            foreach ($locales as $locale) {
                if (! in_array($locale, $allLocales)) {
                    $this->io->error('The provided locale is not valid: ' . $locale);

                    return self::FAILURE;
                }
            }
        } else {
            $locales = $allLocales;
        }

        $commandData = [
            // the command name is passed as the first argument
            'command' => 'l10n:extract',
            '--export' => null,
            '--hide-warnings' => null,
        ];

        try {
            $error = $this->getApplication()->doRun(new ArrayInput($commandData), $output);
            if ($error !== 0) {
                throw new \Exception('The creation of the export-packages failed for unknown reasons.');
            }
            $exportStore = L10nHelper::getStore();
            // remove everything but the zip's
            ZfExtended_Utils::recursiveDelete($exportStore, ['zip'], true, false);
            $exportFiles = [];
            foreach ($allLocales as $locale) {
                @rmdir($exportStore . '/' . $locale); // recursiveDelete did not delete empty dirs ...
                $exportFile = L10nHelper::createTaskZipName($locale);
                if (in_array($locale, $locales)) {
                    if (file_exists($exportStore . '/' . $exportFile)) {
                        $exportFiles[$locale] = $exportStore . '/' . $exportFile;
                    } else {
                        throw new \Exception('The export-file “' . $exportFile . '” can not be found in ' . $exportStore);
                    }
                } else {
                    @unlink($exportStore . '/' . $exportFile);
                }
            }
            $this->importTasks($exportFiles);
            $this->io->success("Created and imported:\n " . implode("\n ", array_values($exportFiles)));

            return self::SUCCESS;
        } catch (\MittagQI\ZfExtended\FileWriteException $e) {
            $this->io->error($e->getMessage() . "\n" . self::CODEFILE_WRITE_ERROR);

            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->io->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Import the given zips into translate5 as tasks / project
     * @throws ReflectionException
     * @throws Zend_Db_Statement_Exception
     * @throws Zend_Exception
     * @throws ZfExtended_Exception
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityConstraint
     * @throws ZfExtended_Models_Entity_Exceptions_IntegrityDuplicateKey
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function importTasks(array $importZips): void
    {
        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        $customerNr = $this->input->getOption(self::CUSTOMER);
        if (is_null($customerNr)) {
            $customer->loadByDefaultCustomer();
        } else {
            $customer->loadByNumber($customerNr);
        }

        $pmLogin = $this->input->getOption(self::PM_LOGIN);
        if (is_null($pmLogin)) {
            $pmGuid = User::SYSTEM_GUID;
        } else {
            $pm = new User();
            $pm->loadByLogin($pmLogin);
            $pmGuid = $pm->getUserGuid();
        }

        foreach ($importZips as $locale => $zipPath) {
            $sourceLocale = L10nHelper::createSourceLocale($locale);
            $worker = new editor_Models_Import_CliImportWorker();
            $worker->init(
                null,
                [
                    'path' => $zipPath,
                    'taskName' => L10nHelper::createTaskName($locale),
                    'pmGuid' => $pmGuid,
                    'customerNumber' => $customer->getNumber(),
                    'source' => $sourceLocale,
                    'targets' => [$locale],
                    'description' => 'description',
                    'workflow' => $customer->getConfig()->runtimeOptions->workflow->initialWorkflow,
                ]
            );
            $worker->queue();
        }
    }
}
