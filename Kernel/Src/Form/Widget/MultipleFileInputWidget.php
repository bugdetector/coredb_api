<?php

namespace Src\Form\Widget;

use CoreDB;
use CoreDB\Kernel\Database\DataType\File;
use CoreDB\Kernel\EntityReference;
use CoreDB\Kernel\Model;
use Exception;
use Src\Entity\File as EntityFile;
use Src\Entity\Translation;
use Src\JWT;
use Src\Views\Image;
use Src\Views\Link;
use Src\Views\Table;
use Src\Views\TextElement;
use Src\Views\ViewGroup;

class MultipleFileInputWidget extends FormWidget
{
    public Model $baseObject;
    public $referenceClass;
    public string $referenceClassEntityName;
    public JWT $fileKeyJwt;

    /**
     * @param string $name
     *  Input name
     * @param Model $baseObject
     *  Base object for file references.
     * @param string $referenceClass
     *  Model class path for file attachment.
     */
    public function __construct(string $name, Model $baseObject, $referenceClass)
    {

        $this->setName($name);
        $this->setBaseObject($baseObject);
        $this->setReferenceClass($referenceClass);
        parent::__construct("{$this->baseObject->entityName}[{$this->referenceClassEntityName}]");

        $this->fileKeyJwt = new JWT();
        $this->fileKeyJwt->setPayload([
            "entity" => $this->referenceClassEntityName,
            "id" => null,
            "field" => $this->getFileFieldNameOfReferenceClass()
        ]);
        $controller = CoreDB::controller();
        $controller->addJsFiles([
            "assets/js/components/file_input.js",
            "assets/js/widget/multiple-file-input-widget.js"
        ]);
    }

    public function setBaseObject(Model $baseObject)
    {
        $this->baseObject = $baseObject;
    }

    public function setReferenceClass($referenceClass)
    {
        $this->referenceClass = $referenceClass;
        $this->referenceClassEntityName = $this->getReferenceClassEntityName();
    }

    protected function getReferenceClassEntityName()
    {
        $entityInfo = CoreDB::config()->getEntityInfoByClass($this->referenceClass);
        if (!$entityInfo) {
            throw new Exception("Entity configuration not provided for reference class.");
        }
        return key($entityInfo);
    }

    protected function getFiles(): array
    {
        $entityName = $this->referenceClassEntityName;
        /** @var EntityReference */
        $fileField = $this->baseObject->{$entityName};
        return $fileField->getCheckeds();
    }

    public function getFileFieldNameOfReferenceClass()
    {
        $referenceClassInstance = new $this->referenceClass();
        foreach ($referenceClassInstance as $fieldName => $field) {
            if ($field instanceof File) {
                return $fieldName;
            }
        }
        throw new Exception("Reference class is not have any File field.");
    }

    public function getFilesTable(): Table
    {
        $table = new Table();
        $table->addClass("multiple-files-content")
        ->addAttribute("data-name", $this->name);
        $table->setHeaders([
            "",
            Translation::getTranslation("file_name"),
            Translation::getTranslation("file_size"),
            "",
        ]);
        $referenceClassEntityName = $this->referenceClassEntityName;
        $fileFieldName = $this->getFileFieldNameOfReferenceClass();
        $data = [];
        foreach ($this->getFiles() as $index => $fileInfo) {
            /** @var EntityFile */
            $file = EntityFile::get($fileInfo->{$fileFieldName}->getValue());
            $row = [];
            if ($file->isImage) {
                $row[0] = ViewGroup::create("div", "mw-50px")
                ->addField(
                    Link::create(
                        "#",
                        Image::create($file->getUrl(), $file->file_name)
                        ->addClass("img-thumbnail ms-2 mw-100")
                    )->addClass("image-preview")
                    ->addAttribute("data-field-name", Translation::getTranslation($fileFieldName))
                );
            } else {
                $row[0] = ViewGroup::create("div", "mw-50px")
                ->addField(
                    ViewGroup::create("span", $file->getFileIconClass() . " fs-2x ms-2 text-primary")
                );
            }
            $row[0]->addField(
                InputWidget::create($this->name . "[{$index}][{$fileFieldName}]")
                ->setType("hidden")
                ->setValue($file->ID->getValue())
            );
            $row[] = Link::create(
                $file->getUrl(),
                $file->file_name
            )->addAttribute("target", "_blank");
            $row[] = $file->sizeConvertToString($file->file_size->getValue());
            $removeKeyJwt = new JWT();
            $removeKeyJwt->setPayload([
                "entity" => $referenceClassEntityName,
                "id" => $fileInfo->ID->getValue()
            ]);
            $row[] = Link::create(
                "#",
                TextElement::create(
                    "<span class='fa fa-trash'></span>"
                )->setIsRaw(true)
            )->addClass("btn btn-sm btn-light-danger multiple-file-delete")
            ->addAttribute("data-key", $removeKeyJwt->createToken())
            ->addAttribute("data-field-name", Translation::getTranslation($fileFieldName));
            $data[] = $row;
        }
        $table->setData($data);
        return $table;
    }

    public function getTemplateFile(): string
    {
        return "multiple-file-input-widget.twig";
    }
}
