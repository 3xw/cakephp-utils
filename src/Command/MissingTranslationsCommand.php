<?php
declare(strict_types=1);

namespace Trois\Utils\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Inflector;

class MissingTranslationsCommand extends Command
{
    use LocatorAwareTrait;

    /**
     * Build the option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription('Find and create missing translations for a model')
            ->addArgument('model', [
                'help' => 'The model name (e.g., Articles or Plugin.Model)',
                'required' => true,
            ])
            ->addArgument('locales', [
                'help' => 'Locales to create translations for (space separated)',
                'required' => true,
            ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $modelName = $args->getArgument('model');
        $localesArg = $args->getArgument('locales');

        if (empty($modelName)) {
            $io->error('Need to pass one Model');
            return static::CODE_ERROR;
        }

        $locales = explode(' ', $localesArg);

        $table = $this->fetchTable($modelName);
        $modelParts = explode('.', $modelName);
        $model = end($modelParts);

        $associationsFields = $table->associations()->keys();
        if (empty($associationsFields)) {
            $io->error('Model not found ...');
            return static::CODE_ERROR;
        }

        $translationsFields = [];
        foreach ($associationsFields as $aField) {
            if (strpos($aField, '_translation') !== false) {
                $field = str_replace($table->getTable() . '_', '', str_replace('_translation', '', $aField));
                $translationsFields[] = $field;
            }
        }

        if (empty($translationsFields)) {
            $io->error('No field to translate found');
            return static::CODE_ERROR;
        }

        if (empty($locales)) {
            $io->error('Need to pass at least one locale');
            return static::CODE_ERROR;
        }

        $availableLocales = Configure::read('I18n.languages');

        foreach ($locales as $locale) {
            if (!in_array($locale, $availableLocales)) {
                $io->error('Locale ' . $locale . ' not found in available languages');
                return static::CODE_ERROR;
            }
        }

        $i18nTable = $this->fetchTable('I18n');

        foreach ($locales as $locale) {
            $alias = Inflector::camelize($locale);
            $elems = $table->find()
                ->leftJoin([$alias => 'i18n'], [
                    $alias . '.foreign_key = ' . $model . '.id',
                    $alias . '.model' => $model,
                    $alias . '.locale' => $locale,
                ])
                ->where([$alias . '.id IS NULL'])
                ->toArray();

            if (empty($elems)) {
                $io->info('All ' . $model . ' are already translated in ' . $locale);
                continue;
            }

            $entities = [];
            foreach ($elems as $elem) {
                foreach ($translationsFields as $field) {
                    $entities[] = [
                        'locale' => $locale,
                        'model' => $model,
                        'foreign_key' => $elem->id,
                        'field' => $field,
                        'content' => ($field == 'slug') ? $elem->slug : '',
                    ];
                }
            }

            $entities = $i18nTable->newEntities($entities);
            $results = $i18nTable->saveMany($entities);

            if (empty($results)) {
                $io->error('No translations was added, an error occurred!');
                return static::CODE_ERROR;
            } else {
                $io->info(count($results) . ' translations was added for ' . count($elems) . ' ' . $model . ' in ' . $locale);
            }
        }

        return static::CODE_SUCCESS;
    }
}
