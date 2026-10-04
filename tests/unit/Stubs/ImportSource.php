<?php
namespace abrain\Einsatzverwaltung\Stubs;

use abrain\Einsatzverwaltung\Import\Sources\AbstractSource;

class ImportSource extends AbstractSource
{
    /** @var string[] */
    private $fields;

    public function __construct(array $fields = [], array $autoMatchFields = [], array $internalFields = [])
    {
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
    public function getDescription(): string
    {
        return 'lorem ipsum';
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
    public function getEntries(array $requestedFields): array
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
    public function getIdentifier(): string
    {
        return 'stub';
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'Stub';
    }

    /**
     * @inheritDoc
     */
    public function getTimeFormat(): string
    {
        return 'H:i:s';
    }
}
