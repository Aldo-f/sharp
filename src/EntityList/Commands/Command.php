<?php

namespace Code16\Sharp\EntityList\Commands;

use Code16\Sharp\Utils\Fields\FieldsContainer;
use Code16\Sharp\Form\Layout\FormLayoutColumn;
use Code16\Sharp\Utils\Fields\HandleFormFields;
use Code16\Sharp\Utils\Traits\HandlePageAlertMessage;
use Code16\Sharp\Utils\Transformers\WithCustomTransformers;
use Illuminate\Contracts\Validation\Factory as Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

abstract class Command
{
    use HandleFormFields, 
       HandlePageAlertMessage,
        WithCustomTransformers;

    protected int $groupIndex = 0;
    protected ?string $commandKey = null;
    
    private ?string $formModalTitle = null;
    private ?string $confirmationText = null;
    private ?string $description = null;

    protected function info(string $message): array
    {
        return [
            "action" => "info",
            "message" => $message
        ];
    }

    protected function link(string $link): array
    {
        return [
            "action" => "link",
            "link" => $link
        ];
    }

    protected function reload(): array
    {
        return [
            "action" => "reload"
        ];
    }

    protected function refresh($ids): array
    {
        return [
            "action" => "refresh",
            "items" => (array)$ids
        ];
    }

    protected function view(string $bladeView, array $params = []): array
    {
        return [
            "action" => "view",
            "html" => view($bladeView, $params)->render()
        ];
    }

    protected function download(string $filePath, string $fileName = null, string $diskName = null): array
    {
        return [
            "action" => "download",
            "file" => $filePath,
            "disk" => $diskName,
            "name" => $fileName
        ];
    }

    protected function streamDownload(string $fileContent, string $fileName): array
    {
        return [
            "action" => "streamDownload",
            "content" => $fileContent,
            "name" => $fileName
        ];
    }

    protected final function configureFormModalTitle(string $formModalTitle): self
    {
        $this->formModalTitle = $formModalTitle;
        return $this;
    }

    protected final function configureDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    protected final function configureConfirmationText(string $confirmationText): self
    {
        $this->confirmationText = $confirmationText;
        return $this;
    }

    /**
     * Check if the current user is allowed to use this Command.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function getGlobalAuthorization(): array|bool
    {
        return $this->authorize();
    }

    public final function getConfirmationText(): ?string
    {
        return $this->confirmationText;
    }
    
    public final function getDescription(): ?string
    {
        return $this->description;
    }

    public final function getFormModalTitle(): ?string
    {
        return $this->formModalTitle;
    }

    /**
     * Build the optional Command config with configure... methods
     */
    public function buildCommandConfig(): void
    {
    }

    /**
     * Build the optional Command form
     */
    public function buildFormFields(FieldsContainer $formFields): void
    {
    }

    /**
     * Build the optional Command form layout.
     */
    public function buildFormLayout(FormLayoutColumn &$column): void
    {
    }

    public final function commandFormConfig(): ?array
    {
        if($this->pageAlertHtmlField === null) {
            return null;
        }
        
        return tap([], function(&$config) {
            $this->appendGlobalMessageToConfig($config);
        });
    }

    public final function form(): array
    {
        return $this->fields();
    }

    public final function formLayout(): ?array
    {
        if($fields = $this->fieldsContainer()->getFields()) {
            $column = new FormLayoutColumn(12);
            $this->buildFormLayout($column);

            if (empty($column->fieldsToArray()["fields"])) {
                foreach ($fields as $field) {
                    $column->withSingleField($field->key());
                }
            }

            return $column->fieldsToArray()["fields"];
        }

        return null;
    }

    public final function setGroupIndex($index): void
    {
        $this->groupIndex = $index;
    }

    public final function setCommandKey(string $key): void
    {
        $this->commandKey = $key;
    }

    public final function groupIndex(): int
    {
        return $this->groupIndex;
    }

    public final function getCommandKey(): string
    {
        return $this->commandKey ?? class_basename($this::class);
    }

    public final function validate(array $params, array $rules, array $messages = []): void
    {
        $validator = app(Validator::class)->make($params, $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException(
                $validator, new JsonResponse($validator->errors()->getMessages(), 422)
            );
        }
    }

    abstract public function label(): ?string;
}