<?php

namespace Darory\Tools\Commands;

use Illuminate\Console\Command;

class DaroryInstall extends Command
{
    protected $signature = 'darory:install';

    protected $description = 'Install Darory Tools package (publishes views, components, stubs)';

    public function handle()
    {
        $this->info('🔧 Publishing Darory Tools assets...');

        $this->callSilent('vendor:publish', ['--tag' => 'darory-views', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'darory-components', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'darory-public', '--force' => true]);
        $this->callSilent('vendor:publish', ['--tag' => 'darory-livewire-classes', '--force' => true]);

        // $this->callSilent('vendor:publish', ['--tag' => 'darory-stubs', '--force' => true]);

        $this->info('Installing intervention/image...');
        exec('composer require intervention/image');
        $this->info('Installation complete.🎉');

        $this->info('✅ Darory assets published successfully!');

        $this->warn('⚠️  Make sure to register your package path in composer.json like:');
        $this->line('"repositories": [{"type": "path", "url": "packages/darory/tools"}]');
        $this->line('Then run:');
        $this->line('    composer require darory/tools:@dev');

        $this->info('All done 🎉');
    }
}
