<?php

namespace Hongyukeji\LaravelTranslate\Commands;

use Hongyukeji\LaravelTranslate\Translators\TranslatorInterface;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Hongyukeji\LaravelTranslate\Translate;
use Hongyukeji\LaravelTranslate\Translates;
use Themsaid\Langman\Manager;
use Themsaid\Langman\Manager as Langman;

class MissingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'translate:missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Translates all source translations that are not set in your target translations';

    protected $translator;

    /**
     * Create a new command instance.
     *
     * @param Translate $translator
     */
    public function __construct(Translate $translator)
    {
        parent::__construct();
        $this->translator = $translator;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $targetLanguages = Arr::wrap(config('translate.target_language'));

        $foundLanguages = count($targetLanguages);
        $this->line('Found ' . $foundLanguages . ' ' . Str::plural('language', $foundLanguages) . ' to translate');

        $missingCount = 0;
        foreach ($targetLanguages as $targetLanguage) {
            $missing = $this->translator->getMissingTranslations($targetLanguage);

            $missingCount += $missing->count();
            $this->line('Found ' . $missing->count() . ' missing keys in ' . $targetLanguage);
        }

        $bar = $this->output->createProgressBar($missingCount);
        $bar->start();

        foreach ($targetLanguages as $targetLanguage) {
            $missing = $this->translator->getMissingTranslations($targetLanguage);

            $translated = $this->translator->translate($targetLanguage, $missing, function () use ($bar) {
                $bar->advance();
            });
            $this->translator->fillLanguageFiles($targetLanguage, $translated);
        }

        $bar->finish();

        $this->info("\nTranslated " . $missingCount . ' missing language keys.');
        $paths = config('translate.paths',[]);
        foreach ($paths as $val){
            var_dump($val);
            app()->bind(Manager::class, function ()use($val) {
                return new Manager(
                    new Filesystem,
                    $val,
                    array_merge(config('langman2.code_paths'), [], []),
                    config('langman2.functions'),
                    config('langman2.target_language')
                );
            });
            $this->translator = app(Translate::class);
            $targetLanguages = Arr::wrap(config('translate.target_language'));

            $foundLanguages = count($targetLanguages);
            $this->line('Found ' . $foundLanguages . ' ' . Str::plural('language', $foundLanguages) . ' to translate');

            $missingCount = 0;
            foreach ($targetLanguages as $targetLanguage) {
                $missing = $this->translator->getMissingTranslations($targetLanguage);

                $missingCount += $missing->count();
                $this->line('Found ' . $missing->count() . ' missing keys in ' . $targetLanguage);
            }

            $bar = $this->output->createProgressBar($missingCount);
            $bar->start();

            foreach ($targetLanguages as $targetLanguage) {
                $missing = $this->translator->getMissingTranslations($targetLanguage);

                $translated = $this->translator->translate($targetLanguage, $missing, function () use ($bar) {
                    $bar->advance();
                });
                $this->translator->fillLanguageFiles($targetLanguage, $translated);
            }

            $bar->finish();

            $this->info("\nTranslated " . $missingCount . ' missing language keys.');
        }
    }
}
