<?php

namespace Darory\Tools\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DaroryEnums extends Command
{
    protected $signature = 'darory:enum';
    protected $description = 'Interactively create a PHP enum in app/Enums';

    public function handle()
    {
        $name = $this->ask('Enter the enum name (e.g., ActivationStatus)');
        $enumClass = Str::studly($name);

        $replaceChar = $this->anticipate(
            'How should spaces be replaced in values? (default: "_")',
            ['_', '-'],
            '_'
        );

        $cases = [];

        while (true) {
            $label = $this->ask("Enter an enum case (e.g., TestMe, test me, test-me), or leave empty to finish");
            if (empty($label)) break;

            // Normalize input
            $normalized = strtolower(preg_replace('/[^a-zA-Z0-9]+/', ' ', $label)); // convert symbols to space
            $caseName = Str::studly($normalized); // "TestMe"
            $caseValue = str_replace(' ', $replaceChar, $normalized); // "test_me" or "test-me"

            $cases[$caseName] = $caseValue;
        }

        if (empty($cases)) {
            $this->error("No enum cases provided. Aborting.");
            return;
        }

        $this->generateEnum($enumClass, $cases);
        $this->info("✅ Enum created: app/Enums/{$enumClass}.php");
    }




    protected function generateEnum(string $name, array $cases)
    {
        $namespace = "App\\Enums";
        $path = app_path("Enums/{$name}.php");

        if (!\Illuminate\Support\Facades\File::isDirectory(app_path('Enums'))) {
            \Illuminate\Support\Facades\File::makeDirectory(app_path('Enums'), 0755, true);
        }

        $caseStrings = [];
        foreach ($cases as $caseName => $caseValue) {
            $caseStrings[] = "    case {$caseName} = '{$caseValue}';";
        }
        $caseBlock = implode("\n", $caseStrings);

        $labels = [];
        foreach ($cases as $caseName => $caseValue) {
            $labels[] = "            self::{$caseName}->value => __('" . \Illuminate\Support\Str::headline($caseValue) . "'),";
        }
        $labelsBlock = implode("\n", $labels);

        $template = <<<PHP
<?php

namespace {$namespace};

enum {$name}: string
{
{$caseBlock}

    public static function labels(): array
    {
        return [
{$labelsBlock}
        ];
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return self::labels()[\$this->value];
    }
}
PHP;

        \Illuminate\Support\Facades\File::put($path, $template);
    }
}
