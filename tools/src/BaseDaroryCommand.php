<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

abstract class BaseDaroryCommand extends Command
{

    protected array $skipTables = ['cache','cache_locks','job_batches','jobs','sessions','migrations', 'failed_jobs', 'password_resets','password_reset_tokens', 'personal_access_tokens'];

    protected function showModelTable(): void
    {
        $data = [];
        $tables = $this->getAllDatabaseTables();
        // dd($tables);
        foreach ($tables as $index => $table) {
            $modelKebab = Str::replace('_', '-', $table);
            $model = Str::studly(Str::singular($table));
            $data[] = [
                '#'          => $index + 1,
                'Table'      => $table,
                'Controller' => $this->fileStatus(app_path("Http/Controllers/{$model}Controller.php")),
                'Store Request'  => $this->fileStatus(app_path("Http/Requests/Store{$model}Request.php")),
                'Update Request' => $this->fileStatus(app_path("Http/Requests/Update{$model}Request.php")),
                'Index'     => $this->fileStatus(resource_path("views/{$modelKebab}/index.blade.php")),
                'Create'    => $this->fileStatus(resource_path("views/{$modelKebab}/create.blade.php")),
                'Edit'      => $this->fileStatus(resource_path("views/{$modelKebab}/edit.blade.php")),
                'Show'      => $this->fileStatus(resource_path("views/{$modelKebab}/show.blade.php")),
            ];
        }

        $this->table(
            ['#', 'Table', 'Controller', 'Store Request', 'Update Request', 'Index','Create', 'Edit', 'Show'],
            $data
        );
    }

    protected function getAllDatabaseTables()
    {
        return array_values(array_diff(array_map('current', DB::select('SHOW TABLES')), $this->skipTables));
    }

    protected function fileStatus(string $path): string
    {
        return File::exists($path) ? '<fg=red>✅</>' : '<fg=red>❌</>';
    }
}
