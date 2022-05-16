<?php

declare(strict_types=1);

class Editor_Plugins_Tmmaintenance_ApiController extends ZfExtended_RestController
{
    // TODO move somewhere
    private const JSON_DEFAULT_DEPTH = 512;

    private Zend_Session_Namespace $session;

    protected $entityClass = editor_Models_Segment::class;

    public function init()
    {
        parent::init();

        $this->session = new Zend_Session_Namespace('user');
    }

    public function indexAction(): void
    {

    }

    public function localesAction(): void
    {
        $data = [
            'locale' => $this->resolveLocale()
        ];

        $data['l10n'] = $this->readLocalization();

        $this->view->assign($data);
    }

    public function tmsAction(): void
    {
        $data = [
            'tms' => [
                ['name' => 'TM1', 'value' => 'TM1'],
                ['name' => 'TM2', 'value' => 'TM2'],
                ['name' => 'TM3', 'value' => 'TM3'],
            ]
        ];

        $this->view->assign($data);
    }

    public function searchAction(): void
    {
        $data = [
            [
                'CleantName' => 'Client name',
                'SourceLanguage' => 'en',
                'TargetLanguage' => 'de',
                'SourceText' => 'Segment 1',
                'TargetText' => 'Target 1',
                'Author' => 'Leon',
                'CreationDate' => (new \DateTime('yesterday'))->format(\DateTimeInterface::RFC2822),
                'DocumentName' => 'Document name',
                'AdditionalInfo' => 'Some additional info',
            ],
            [
                'CleantName' => 'Client name',
                'SourceLanguage' => 'en',
                'TargetLanguage' => 'de',
                'SourceText' => 'Segment 2',
                'TargetText' => 'Target 2',
                'Author' => 'Leon',
                'CreationDate' => (new \DateTime('yesterday'))->format(\DateTimeInterface::RFC2822),
                'DocumentName' => 'Document name',
                'AdditionalInfo' => 'Some additional info',
            ],
        ];

        $this->view->assign($data);
    }

    private function readLocalization(): array
    {
        $data = [];

        $fileContent = file_get_contents(__DIR__ . '/../locales/' . $this->session->data->locale . '.json');
        try {
            $data = json_decode($fileContent, true, self::JSON_DEFAULT_DEPTH, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            // TODO do something
        }

        return $data;
    }

    private function resolveLocale(): string
    {
        $requestedLocale = (string)$this->getParam('locale');

        // TODO get locales from globally defined
        if ($this->getParam('locale')
            && in_array($requestedLocale, ['en', 'de'], true)
        ) {
            $this->session->data->locale = $requestedLocale;
        }

        return $this->session->data->locale;
    }
}
