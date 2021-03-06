<?php

namespace Distilleries\Contentful\Commands\Generators;

use Illuminate\Support\Str;

class Models extends AbstractGenerator
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'contentful:generate-models';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Generate Eloquent models from Contentful content-types';

    /**
     * Execute the console command.
     *
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function handle()
    {
        $contentTypes = $this->api->contentTypes();

        if (! empty($contentTypes['items'])) {
            array_unshift($contentTypes['items'], $this->assetContentType());

            foreach ($contentTypes['items'] as $contentType) {
                if ($contentType['sys']['id'] !== 'asset') {
                    $this->info('Content-Type: ' . Str::upper($contentType['name']));
                    $file = $this->createMapper($contentType);
                    $this->line('Mapper "' . $file . '" created');
                    $file = $this->createModel($contentType);
                    $this->line('Model "' . $file . '" created');
                }
            }
        }
    }

    /**
     * Create migration file for given content-type.
     *
     * @param  array  $contentType
     * @return string
     * @throws \Exception
     */
    protected function createModel(array $contentType): string
    {
        $table = $this->tableName($contentType['sys']['id']);
        $model = Str::studly(Str::singular($table));

        $stubPath = __DIR__ . '/stubs/model.stub';
        $destPath = rtrim(config('contentful.generator.model'), '/') . '/' . $model . '.php';

        return static::writeStub($stubPath, $destPath, [
            'model' => $model,
            'table' => $table,
            'getters' => $this->modelGetters($table, $contentType['fields']),
            'properties' => $this->modelProperties($table, $contentType['fields']),
        ]);
    }

    /**
     * Return model mapper.
     *
     * @param  array  $contentType
     * @return string
     */
    protected function createMapper(array $contentType): string
    {
        $table = $this->tableName($contentType['sys']['id']);
        $model = Str::studly(Str::singular($table));

        $stubPath = __DIR__ . '/stubs/mapper.stub';
        $destPath = rtrim(config('contentful.generator.mapper'), '/') . '/' . $model . 'Mapper.php';

        return static::writeStub($stubPath, $destPath, [
            'model' => $model
        ]);
    }

    /**
     * Return model getters.
     *
     * @param  string  $table
     * @param  array  $fields
     * @return string
     * @throws \Exception
     */
    protected function modelGetters($table, $fields): string
    {
        $getters = [];
        foreach ($fields as $field) {
            if ($this->isFieldEnabled($field)) {
                $fieldDefinition = $this->fieldDefinition($table, $field);
                $getters[] = $fieldDefinition->modelGetter();
            }
        }

        $getters = rtrim(implode("\n", array_map(function ($getter) {
            return $getter;
        }, $getters)));

        return ! empty($getters) ? "\n" . $getters : "\n\t\t//";
    }

    /**
     * Return model properties doc-block.
     *
     * @param  string  $table
     * @param  array  $fields
     * @return string
     * @throws \Exception
     */
    protected function modelProperties($table, $fields): string
    {
        $properties = [];
        foreach ($fields as $field) {
            if ($this->isFieldEnabled($field)) {
                $fieldDefinition = $this->fieldDefinition($table, $field);
                $properties[] = $fieldDefinition->modelProperty();
            }
        }

        return ! empty($properties) ? "\n" . implode("\n", $properties) : '';
    }
}
