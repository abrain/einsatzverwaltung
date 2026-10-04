<?php
namespace abrain\Einsatzverwaltung\Stubs;

use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;

class ImportSource extends AbstractSource
{
    /** @var string[] */
    private $fields;

    public function __construct(array $fields = [], array $autoMatchFields = [], array $internalFields = [])
    {
        parent::__construct('stub', 'Stub', 'lorem ipsum');
        $this->fields = $fields;
        $this->autoMatchFields = $autoMatchFields;
        $this->internalFields = $internalFields;
    }

    /**
     * @inheritDoc
     */
    public function checkPreconditions(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d';
    }

    /**
     * @inheritDoc
     */
    public function getEntries(array $requestedFields = []): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @inheritDoc
     */
    public function getTimeFormat(): string
    {
        return 'H:i:s';
    }
}
