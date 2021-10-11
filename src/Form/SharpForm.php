<?php

namespace Code16\Sharp\Form;

use Code16\Sharp\Exceptions\Form\SharpFormUpdateException;
use Code16\Sharp\Form\Layout\FormLayout;
use Code16\Sharp\Utils\Fields\FieldsContainer;
use Code16\Sharp\Utils\Fields\HandleFormFields;
use Code16\Sharp\Utils\SharpNotification;
use Code16\Sharp\Utils\Traits\HandleCustomBreadcrumb;
use Code16\Sharp\Utils\Traits\HandlePageAlertMessage;
use Code16\Sharp\Utils\Transformers\WithCustomTransformers;

abstract class SharpForm
{
    use WithCustomTransformers, 
        HandleFormFields,
        HandlePageAlertMessage,
        HandleCustomBreadcrumb;

    protected ?FormLayout $formLayout = null;
    protected bool $displayShowPageAfterCreation = false;

    public final function formLayout(): array
    {
        if($this->formLayout === null) {
            $this->formLayout = new FormLayout();
            $this->buildFormLayout($this->formLayout);
        }
        
        return $this->formLayout->toArray();
    }

    public function formConfig(): array
    {
        return tap(
            [
                "hasShowPage" => $this->displayShowPageAfterCreation
            ],
            function(&$config) {
                $this->appendBreadcrumbCustomLabelAttribute($config);
                $this->appendGlobalMessageToConfig($config);
            }
        );
    }

    public final function instance($id): array
    {
        return collect($this->find($id))
            // Filter model attributes on actual form fields
            ->only(
                array_merge(
                    $this->breadcrumbAttribute ? [$this->breadcrumbAttribute] : [],
                    $this->getDataKeys()
                )
            )
            ->all();
    }

    public final function newInstance(): ?array
    {
        $data = collect($this->create())
            // Filter model attributes on actual form fields
            ->only(
                array_merge(
                    $this->breadcrumbAttribute ? [$this->breadcrumbAttribute] : [],
                    $this->getDataKeys()
                )
            )
            ->all();

        return sizeof($data) ? $data : null;
    }

    public final function hasDataLocalizations(): bool
    {
        foreach($this->fields() as $field) {
            if($field["localized"] ?? false) {
                return true;
            }
            
            if($field["type"] === "list") {
                foreach($field["itemFields"] as $itemField) {
                    if($itemField["localized"] ?? false) {
                        return true;
                    }
                }
            }
        }
        
        return false;
    }

    public function getDataLocalizations(): array
    {
        return [];
    }

    public function buildFormConfig(): void
    {
    }

    protected function configureDisplayShowPageAfterCreation(bool $displayShowPage = true): self
    {
        $this->displayShowPageAfterCreation = $displayShowPage;
        
        return $this;
    }

    public function isDisplayShowPageAfterCreation(): bool
    {
        return $this->displayShowPageAfterCreation;
    }

    public final function updateInstance($id, $data)
    {
        list($formattedData, $delayedData) = $this->formatRequestData($data, $id, true);

        $id = $this->update($id, $formattedData);

        if($delayedData) {
            // Some formatters asked to delay their handling after a first pass.
            // Typically, this is used if the formatter needs the id of the
            // instance: in a creation case, we must store it first.
            if(!$id) {
                throw new SharpFormUpdateException(
                    sprintf("The update method of [%s] must return the instance id", basename(get_class($this)))
                );
            }

            $this->update($id, $this->formatRequestData($delayedData, $id, false));
        }
        
        return $id;
    }

    public function storeInstance($data)
    {
        return $this->updateInstance(null, $data);
    }

    /**
     * Pack new Model data as JSON.
     */
    public function create(): array
    {
        $attributes = collect($this->getDataKeys())
            ->flip()
            ->map(function() {
                return null;
            })
            ->all();

        // Build a fake Model class based on attributes
        return $this->transform(new class($attributes) extends \stdClass
        {
            public function __construct($attributes)
            {
                $this->attributes = $attributes;

                foreach($attributes as $name => $value) {
                    $this->$name = $value;
                }
            }
            public function toArray()
            {
                return $this->attributes;
            }
        });
    }

    /**
     * Display a notification next time entity list is shown.
     */
    public function notify(string $title): SharpNotification
    {
        return (new SharpNotification($title));
    }

    /**
     * Retrieve a Model for the form and pack all its data as JSON.
     */
    abstract function find(mixed $id): array;

    /**
     * Update the Model of id $id with $data
     */
    abstract function update(mixed $id, array $data);

    /**
     * Delete Model of id $id
     */
    abstract function delete(mixed $id): void;

    /**
     * Build form fields
     */
    abstract function buildFormFields(FieldsContainer $formFields): void;

    /**
     * Build form layout
     */
    abstract function buildFormLayout(FormLayout $formLayout): void;
}