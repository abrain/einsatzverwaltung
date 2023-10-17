<?php
namespace abrain\Einsatzverwaltung\Import;

/**
 * Representation of a single step of the import process
 * @package abrain\Einsatzverwaltung\Import
 */
class Step
{
    /**
     * @var string[]
     */
    private $arguments;

    /**
     * @var string
     */
    private $buttonText;

    /**
     * @var string
     */
    private $slug;

    /**
     * @var string
     */
    private $title;

    /**
     * Step constructor.
     *
     * @param string $slug A unique identifier used to distinguish between different steps
     * @param string $title The page title during this step
     * @param string $buttonText The text to label a button that leads to this step
     * @param string[] $arguments A list of argument names that should be carried over to the next step
     */
    public function __construct(string $slug, string $title, string $buttonText, array $arguments = [])
    {
        $this->slug = $slug;
        $this->title = $title;
        $this->buttonText = $buttonText;
        $this->arguments = $arguments;
    }

    /**
     * @return string[]
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function getButtonText(): string
    {
        return $this->buttonText;
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
