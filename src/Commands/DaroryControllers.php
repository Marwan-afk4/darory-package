<?php

namespace Darory\Tools\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;

class DaroryControllers extends Command
{
    protected $signature = 'darory:controllers {model?} {--all} {displayOnly?} {--missing}';
    protected $description = 'Generate Laravel controllers based on existing models';

    protected array $skipTables = ['cache','cache_locks','job_batches','jobs','sessions','migrations', 'failed_jobs', 'password_resets','password_reset_tokens', 'personal_access_tokens'];

    public function handle()
    {
        if (app()->environment('production')) {
            return $this->error("This command cannot be executed in production.");
        }

        $this->info("✨ Darory Controllers Generator ✨");

        if ($model = $this->argument('model')) {
            return $this->generateController($model);
        }

        if ($this->option('all')) {
            return $this->generateAllControllers();
        }

        $this->handleInteractiveMode();
    }

    protected function handleInteractiveMode(): void
    {
        $models = $this->getAllModels();
        if (empty($models)) {
            $this->error("❌ No models found in the `app/Models` directory.");
            return;
        }

        $this->showModelTable($models);

        if ($this->option('missing')) {
            $this->generateMissingControllers($models);
            return;
        }

        $input = $this->ask("Enter model numbers (comma-separated), '0' for all, or 'm' for missing:");

        match ($input) {
            '0' => $this->generateAllControllers(),
            'm' => $this->call('darory:controllers', ['--missing' => true]),
            default => $this->generateSelectedControllers($models, $input)
        };
    }

    protected function showModelTable(array $models): void
    {
        $data = [];
        foreach ($models as $index => $model) {
            $modelKebab = Str::kebab(Str::plural($model));
            $data[] = [
                '#'          => $index + 1,
                'Model'      => $model,
                'Controller' => $this->fileStatus(app_path("Http/Controllers/{$model}Controller.php")),
                'Store Request'  => $this->fileStatus(app_path("Http/Requests/Store{$model}Request.php")),
                'Update Request' => $this->fileStatus(app_path("Http/Requests/Update{$model}Request.php")),
                'Index'     => $this->fileStatus(resource_path("views/{$modelKebab}/index.blade.php")),
                'Create'    => $this->fileStatus(resource_path("views/{$modelKebab}/create.blade.php")),
                'Edit'      => $this->fileStatus(resource_path("views/{$modelKebab}/edit.blade.php")),
                'Show'      => $this->fileStatus(resource_path("views/{$modelKebab}/show.blade.php")),
            ];
        }

        $this->table(['#', 'Model', 'Controller', 'Store Request', 'Update Request', 'Index','Create', 'Edit', 'Show'], $data);
    }

    protected function generateSelectedControllers(array $models, string $input): void
    {
        $indexes = array_map('trim', explode(',', $input));
        foreach ($indexes as $index) {
            $i = (int) $index - 1;
            if (isset($models[$i])) {
                $this->generateController($models[$i]);
            } else {
                $this->error("❌ Invalid selection: $index");
            }
        }
    }

    protected function generateMissingControllers(array $models): void
    {
        $missing = array_filter($models, fn($m) => !File::exists(app_path("Http/Controllers/{$m}Controller.php")));
        if (empty($missing)) {

            $this->info("✅ All controllers already exist.");
            return;
        }

        foreach ($missing as $model) {
            $this->generateController($model);
        }
    }

    protected function generateAllControllers(): void
    {
        foreach ($this->getAllModels() as $model) {
            $this->generateController($model);
        }
    }

    protected function generateController(string $model): void
    {
        $controllerName = "{$model}Controller";
        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");

        // if (File::exists($controllerPath) && !$this->confirm("The controller '$controllerName' exists. Overwrite?")) {
        //     $this->info("⏩ Skipped $controllerName.");
        //     return;
        // }

        $stubPath = app_path('Console/Commands/stubs/controller.stub');
        if (!File::exists($stubPath)) {
            $this->error("❌ Missing stub at: $stubPath");
            return;
        }

        $relations = $this->getBelongsToRelations($model);
        $replacements = $this->getStubReplacements($model, $relations);
        $template = str_replace(array_keys($replacements), array_values($replacements), File::get($stubPath));

        $this->info("Template for $controllerName:");
        //$this->line($template);



        if ($this->argument('displayOnly') ? true : false) {
            $this->line($template);
            return;
        }

        if (File::exists($controllerPath)) {
            $existingContent = File::get($controllerPath);

            $outputBuilder = new UnifiedDiffOutputBuilder("--- Template\n+++ Existing\n");
            $differ = new Differ($outputBuilder);
            $diff = $differ->diff( $existingContent,$template);
            $this->line($diff);
            //return;
        }

        if (File::exists($controllerPath) && !$this->confirm("The controller '$controllerName' exists. Overwrite?")) {
            $this->info("⏩ Skipped $controllerName.");
            return;
        }

        File::put($controllerPath, $template);
        $this->info("✅ Controller '$controllerName' created successfully.");
    }

    protected function getAllModels(): array
    {
        $path = app_path('Models');
        if (!is_dir($path)) return [];

        return collect(scandir($path))
            ->filter(fn($file) => str_ends_with($file, '.php'))
            ->map(fn($file) => pathinfo($file, PATHINFO_FILENAME))
            ->values()
            ->toArray();
    }

    protected function fileStatus(string $relativePath): string
    {
        return File::exists($relativePath) ? '<fg=red>✅</>' : '<fg=red>❌</>';
    }

    protected function getRelationsOfType($modelName, $relationType)
    {
        if (!class_exists("App\\Models\\$modelName")) {
            return [];
        }

        $instance = new ("App\\Models\\$modelName");
        $methods = get_class_methods($instance);
        $relations = [];

        foreach ($methods as $method) {
            try {
                $reflection = new \ReflectionMethod($instance, $method);

                // Skip non-public, static, or parameterized methods
                if (!$reflection->isPublic() || $reflection->isStatic() || $reflection->getNumberOfParameters() > 0) {
                    continue;
                }

                $result = $reflection->invoke($instance);
                if ($result instanceof $relationType) {
                    $relations[] = $method;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $relations;
    }

    protected function getBelongsToRelations($modelName)
    {
        if (!class_exists("App\\Models\\$modelName")) {
            return [];
        }

        $modelInstance = new ("App\\Models\\$modelName");
        $methods = get_class_methods($modelInstance);
        $relations = [];

        foreach ($methods as $method) {
            try {
                $reflection = new \ReflectionMethod($modelInstance, $method);

                if (!$reflection->isPublic() || $reflection->isStatic() || $reflection->getNumberOfParameters() > 0) {
                    continue;
                }

                $relation = $reflection->invoke($modelInstance);

                if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                    $relations[] = [
                        'method' => $method,
                        'foreign_key' => $relation->getForeignKeyName(),
                        'route' => (Str::plural(Str::kebab(class_basename($relation->getRelated()::class))))
                    ];
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return $relations;
    }

    protected function getStubReplacements(string $model, array $relations): array
    {
        $useStatements = "use App\\Models\\$model;\n";
        $relationsList = '';
        $compactVars = [];
        $forignKeys = [];
        foreach ($relations as $relation) {
            $relatedModel = Str::studly($relation['method']);
            $plural = Str::plural($relation['method']);
            $modelClass = "App\\Models\\$relatedModel";

            $useStatements .= "use $modelClass;\n";
            $compactVars[] = "'$plural'";

            // Check if model class exists and has 'name' column
            $column = 'name';
            if (class_exists($modelClass)) {
                $instance = new $modelClass;
                if (!Schema::hasColumn($instance->getTable(), 'name')) {
                    $column = 'id';
                }
            }

            $relationsList .= "\$$plural = $relatedModel::orderBy('$column')->pluck('$column', 'id')->toArray();\n        ";
            $forignKeys[] = $relation['foreign_key'];
        }


        $tableName = Str::plural(Str::snake($model));
        $kebabName = Str::kebab(Str::headline($tableName));
        $modelVar = lcfirst($model);
        $modelsVar = lcfirst(Str::plural($model));
        $indexCompact = "'$modelsVar','sortField','sortOrder'" . (!empty($compactVars) ? ',' . implode(',', $compactVars) : '');
        $editCompact = "'$modelVar'" . (!empty($compactVars) ? ',' . implode(',', $compactVars) : '');
        $withQuery = empty($relations) ? '' : "with(['" . implode("', '", array_column($relations, 'method')) . "'])";

        $whereQuery = '';

        if (!empty($forignKeys)) {
            foreach ($forignKeys as $forignKey) {
                $whereQuery .= "\n\t\t\t->";
                $whereQuery .= "when(request('".$forignKey."'), function (\$query) {\n\t\t\t\t\$query->where('".$forignKey."', request('".$forignKey."'));\n\t\t\t})";
            }
            $whereQuery .= "\n\t\t\t->";
        }

        return [
            '{{useStatements}}'           => $useStatements,
            '{{model}}'                   => $model,
            '{{tableName}}'               => $tableName,
            '{{indexQuery}}'              => '$'.$modelsVar.' = '.$model.'::'.$withQuery.$whereQuery."orderBy(\$sortField, \$sortOrder)->paginate(30);",
            '{{modelVariable}}'           => $modelVar,
            '{{modelsVariable}}'          => $modelsVar,
            '{{relationsList}}'           => $relationsList,
            '{{indexReturnStatement}}'    => "return view('{$kebabName}.index', compact($indexCompact));",
            '{{createReturnStatement}}'   => "return view('{$kebabName}.create'" . (empty($compactVars) ? '' : ", compact(" . implode(',', $compactVars) . ")") . ");",
            '{{editReturnStatement}}'     => "return view('{$kebabName}.edit', compact($editCompact));",
            '{{kebabName}}'               => $kebabName
        ];
    }

    protected function getAllDatabaseTables()
    {
        return array_values(array_diff(array_map('current', DB::select('SHOW TABLES')), $this->skipTables));
    }
}
