<?php

namespace Distilleries\Contentful\Commands\Generators\Definitions;

use Illuminate\Support\Str;

abstract class BaseDefinition implements DefinitionInterface
{
    /**
     * Model table.
     *
     * @var string
     */
    protected $table;

    /**
     * Field data.
     *
     * @var array
     */
    protected $field;

    /**
     * BaseDefinition constructor.
     *
     * @param  string  $table
     * @param  array  $field
     * @return void
     */
    public function __construct($table, $field)
    {
        $this->table = $table;

        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function modelGetter();

    /**
     * Return normalized ID of current field.
     *
     * @return string
     */
    protected function id(): string
    {
        return mb_strtolower(Str::snake($this->field['id']));
    }

    /**
     * Write stub to destination path with given string replacements.
     *
     * Return relative base path of destination path.
     *
     * @param  string  $stubPath
     * @param  array  $replacements
     * @return string
     */
    public static function getStub(string $stubPath, array $replacements = []): string
    {
        $content = file_get_contents($stubPath);

        foreach ($replacements as $key => $value) {
            $content = str_replace('{{' . mb_strtoupper($key) . '}}', $value, $content);
        }

        return $content;
    }
}
