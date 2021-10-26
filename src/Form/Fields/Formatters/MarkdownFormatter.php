<?php

namespace Code16\Sharp\Form\Fields\Formatters;

use Code16\Sharp\Form\Fields\SharpFormField;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Str;

class MarkdownFormatter extends SharpFieldFormatter
{
    function toFront(SharpFormField $field, $value)
    {
        return [
            "text" => $value,
        ];
    }

    function fromFront(SharpFormField $field, string $attribute, $value)
    {
        $content = $value['text'] ?? '';
        
        if(is_array($content)) {
            // Field is localized
            foreach($content as $locale => $singleText) {
                $content[$locale] = preg_replace(
                    '/\R/', "\n",
                    $this->handleUploadedFiles(
                        $singleText, 
                        $value["files"] ?? [],
                        $field,
                        $attribute
                    )
                );
            }
            return $content;
        }
        
        return preg_replace(
            '/\R/', "\n", 
            $this->handleUploadedFiles(
                $content, 
                $value["files"] ?? [], 
                $field,
                $attribute
            )
        );
    }

    protected function handleUploadedFiles(string $text, array $files, SharpFormField $field, string $attribute): string
    {
        if(count($files)) {
            $dom = $this->getDomDocument($text);
            $uploadFormatter = app(UploadFormatter::class);

            foreach($files as $file) {
                $upload = $uploadFormatter
                    ->setInstanceId($this->instanceId)
                    ->fromFront($field, $attribute, $file);

                if(isset($upload["file_name"])) {
                    // New file was uploaded. We have to update the name of the file in the markdown
                    
                    /** @var DOMElement $domElement */
                    $domElement = collect($dom->getElementsByTagName('x-sharp-file'))
                        ->merge($dom->getElementsByTagName('x-sharp-image'))
                        ->first(function(DOMElement $uploadElement) use ($file) {
                            return $uploadElement->getAttribute("name") === $file["name"];
                        });
                    
                    if($domElement) {
                        $domElement->setAttribute("name", basename($upload["file_name"]));
                        $domElement->setAttribute("path", $upload["file_name"]);
                        $domElement->setAttribute("disk", $upload["disk"]);
                    }
                }
            }

            return $this->formatDomStringValue($dom);
        }
        
        return $text;
    }
    
    protected function getDomDocument(string $content): DOMDocument
    {
        return tap(new DOMDocument(), function(DOMDocument $dom) use ($content) {
            @$dom->loadHTML("<body>$content</body>");
        });
    }

    protected function formatDomStringValue(DOMDocument $dom): string
    {
        $body = $dom->getElementsByTagName('body')->item(0);
        
        return trim(Str::replace(['<body>', '</body>'], '', $dom->saveHTML($body)));
    }
}
