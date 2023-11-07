<?php

namespace Translate5\MaintenanceCli\Command;

use editor_Models_Customer_Customer;
use editor_Models_Import_CliImportWorker;
use ReflectionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Exception;
use ZfExtended_Factory;
use ZfExtended_Models_Entity_NotFoundException;
use ZfExtended_Models_User as User;
use ZfExtended_Utils;
use ZfExtended_Zendoverwrites_Translate;
use ZipArchive;

class L10nTaskcreateCommand extends Translate5AbstractCommand
{
    // the name of the command (the part after "bin/console")
    const WORKFILES = 'workfiles';
    const PIVOTFILES = 'relais';
    const ZXLIFF = '.zxliff';
    const TARGET_LANGUAGES = 'targetLanguages';
    const REVIEW = 'review';
    const PIVOT = 'pivot';
    const KEEP_TARGET = 'keep-target';
    const PM_LOGIN = 'pm-login';
    const CUSTOMER = 'customer';
    protected static $defaultName = 'l10n:task:create';

    private string $zip;

    private string $sourceLanguage = 'de';
    private ZipArchive $zipArchive;
    private array $zxlfCleanUp = [];
    private bool $isReview = false;
    private array $targetLanguages = [];

    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Converts the texts of the current instance into a translation or review task.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Converts the texts of the current instance into a translation (default) or review task.' .
                'Attention: to be reviewed JSON files behaves differently since they are monolingual!'
            );

        $this->addArgument(
            self::TARGET_LANGUAGES,
            InputArgument::REQUIRED | InputArgument::IS_ARRAY,
            'The target language for which the task should be created (review: existing language package, ' .
            'translation: for new languages)'
        );

        $this->addOption(
            self::REVIEW,
            'r',
            InputOption::VALUE_NONE,
            'Make a review task, out of the existing language packages. One project per given target language'
        );

        $this->addOption(
            self::PIVOT,
            'p',
            InputOption::VALUE_REQUIRED,
            'Give the RFC 5646 value of the language to be used as pivot language'
        );

        $this->addOption(
            self::KEEP_TARGET,
            'k',
            InputOption::VALUE_NONE,
            'For translations: keep the original target (so make new translation packages but with existing target)'
        );

        $this->addOption(
            self::PM_LOGIN,
            'u',
            InputOption::VALUE_REQUIRED,
            'The login of the pm associated to the task. If omitted, the Systemuser is used ' .
            'and PM must be changed afterwards'
        );

        $this->addOption(
            self::CUSTOMER,
            'c',
            InputOption::VALUE_REQUIRED,
            'The customer number of the customer to be used. Defaults to defaultcustomer.'
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

        $this->isReview = (bool)$this->input->getOption(self::REVIEW);
        $this->targetLanguages = $this->input->getArgument(self::TARGET_LANGUAGES);

        if ($this->isReview) {
            // for review we create one package per language
            foreach ($this->targetLanguages as $targetLanguage) {
                $zipName = 'translate5-' . $this->sourceLanguage . '-' . $targetLanguage . '-REVIEW.zip';
                $this->createPackage($targetLanguage, $zipName);
            }
        } else {
            // for translation we may create one project for each target language
            $zipName = 'translate5-' . $this->sourceLanguage . '-TRANSLATION.zip';
            $this->createPackage($this->sourceLanguage, $zipName);
        }

        $this->zxlfCleanUp = array_unique($this->zxlfCleanUp);
        foreach ($this->zxlfCleanUp as $toDelete) {
            unlink($toDelete);
        }

        $this->io->writeln('Please remove non-wanted private Plugin XLFs manually!!!');
        $this->io->writeln('After translating and exporting from Translate5, rename *.zxliff back to *.xliff');

        return self::SUCCESS;
    }

    /**
     * Create the zip packages with the translation data
     * @param string $targetLanguage
     * @param string $zipName
     * @throws ReflectionException
     * @throws Zend_Exception
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    protected function createPackage(string $targetLanguage, string $zipName): void
    {
        $this->zip = APPLICATION_DATA . '/' . $zipName;

        if (file_exists($this->zip)) {
            unlink($this->zip);
        }

        $this->zipArchive = new ZipArchive();
        $this->zipArchive->open($this->zip, ZipArchive::CREATE);

        $pivotLanguage = $this->input->getOption(self::PIVOT);
        $keepTarget = (bool)$this->input->getOption(self::KEEP_TARGET);

        $this->processXliffFiles($targetLanguage, keepTarget: $keepTarget);
        $this->addJsonFile($targetLanguage);

        if (!is_null($pivotLanguage)) {
            $this->processXliffFiles($targetLanguage, $pivotLanguage);
            $this->addJsonFile($targetLanguage, $pivotLanguage);
        }

        if ($this->zipArchive->close()) {
            $this->importPackage($targetLanguage, $pivotLanguage);
            $this->io->success('created and imported ' . $this->zip);
        } else {
            $this->io->error($this->zipArchive->getStatusString());
        }
    }

    /**
     * @throws Zend_Exception
     */
    private function processXliffFiles(
        string  $targetLanguage,
        ?string $pivotLanguage = null,
        bool    $keepTarget = false
    ): void {
        $xliffFiles = $this->getXliffFiles($targetLanguage);

        foreach ($xliffFiles as $filename) {
            $zxlfFilename = str_replace('.xliff', self::ZXLIFF, $filename);
            $filenameInZip = str_replace(APPLICATION_ROOT, '', $zxlfFilename);

            if (is_null($pivotLanguage)) {
                $filenameInZip = self::WORKFILES . $filenameInZip;
            } else {
                $filenameInZip = self::PIVOTFILES . str_replace(
                        '/' . $pivotLanguage . self::ZXLIFF,
                        '/' . $targetLanguage . self::ZXLIFF,
                        $filenameInZip
                    );
            }

            if ($this->isReview || !is_null($pivotLanguage) || $keepTarget) {
                $this->addToZip($filename, $filenameInZip);
            } else {
                copy($filename, $zxlfFilename);
                $this->removeTargetContent($zxlfFilename);
                $this->zxlfCleanUp[] = $zxlfFilename;
                $this->addToZip($zxlfFilename, $filenameInZip);
            }
        }
    }

    /**
     * gets the available xlf files to a given language
     * @throws Zend_Exception
     */
    private function getXliffFiles(string $language): array
    {
        $xliffFiles = [];
        $paths = ZfExtended_Zendoverwrites_Translate::getInstance()->getTranslationDirectories();
        foreach ($paths as $path) {
            $path = $path . $language . '.xliff';
            if (!file_exists($path)) {
                continue;
            }
            $xliffFiles[] = $path;
        }

        return $xliffFiles;
    }

    private function addToZip($localFilename, $nameInZip): void
    {
        $this->zipArchive->addFile($localFilename, $nameInZip);
    }

    /**
     * Clean the existing target content to produce a translation task
     * @param $filename
     * @return void
     */
    private function removeTargetContent($filename): void
    {
        $content = file_get_contents($filename);
        $content = preg_replace('#<target>.*?</target>#s', '<target></target>', $content);
        file_put_contents($filename, $content);
    }

    /**
     * gets the available JSON file to a given language, currently hardcoded since only one!
     * @param string $language
     * @param string|null $pivotLanguage
     * @return void
     */
    private function addJsonFile(string $language, ?string $pivotLanguage = null): void
    {
        $jsonName = 'application/modules/editor/PrivatePlugins/TermPortal/locales/%s.json';
        if (is_null($pivotLanguage)) {
            $nameInZip = self::WORKFILES . '/' . sprintf($jsonName, $language);
        } else {
            $nameInZip = self::PIVOTFILES . '/' . sprintf($jsonName, $pivotLanguage);
        }

        $this->addToZip(sprintf($jsonName, $language), $nameInZip);
    }

    /**
     * Import the package into translate5 as tasks / project
     * @param string $targetLanguage
     * @param string|null $pivotLanguage
     * @return void
     * @throws ReflectionException
     * @throws ZfExtended_Models_Entity_NotFoundException
     */
    private function importPackage(string $targetLanguage, ?string $pivotLanguage): void
    {
        $customer = ZfExtended_Factory::get(editor_Models_Customer_Customer::class);
        $customerNr = $this->input->getOption(self::CUSTOMER);
        if (is_null($customerNr)) {
            $customer->loadByDefaultCustomer();
        } else {
            $customer->loadByNumber($customerNr);
        }

        if ($this->isReview) {
            $taskName = 'Translate5 REVIEW ' . $targetLanguage . ' ' . ZfExtended_Utils::getAppVersion();
        } else {
            $taskName = 'Translate5 TRANSLATION ' . ZfExtended_Utils::getAppVersion();
        }

        $pmLogin = $this->input->getOption(self::PM_LOGIN);
        if (is_null($pmLogin)) {
            $pmGuid = User::SYSTEM_GUID;
        } else {
            $pm = new User;
            $pm->loadByLogin($pmLogin);
            $pmGuid = $pm->getUserGuid();
        }

        $worker = new editor_Models_Import_CliImportWorker();
        $worker->init(
            null,
            [
                'path' => $this->zip,
                'taskName' => $taskName,
                'pmGuid' => $pmGuid,
                'customerNumber' => $customer->getNumber(),
                'source' => $this->sourceLanguage,
                'pivotLanguage' => $pivotLanguage,
                'targets' => $this->isReview ? [$targetLanguage] : $this->targetLanguages,
                'description' => 'description',
                'workflow' => $customer->getConfig()->runtimeOptions->workflow->initialWorkflow
            ]
        );
        $worker->queue();
    }
}
