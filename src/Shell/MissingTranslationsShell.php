<?php
namespace Trois\Utils\Shell;

use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Console\Shell;

class MissingTranslationsShell extends Shell
{
  public function main($model, ...$locales)
  {
    if(empty($model)) return $this->err('Need to pass one Model');


    $this->loadModel($model);
    $model = explode('.', $model);
    $model = end($model);

    $associationsFields = $this->$model->associations()->keys();
    if(empty($associationsFields)) return $this->err('Model not found ...');

    $transaltionsFields = [];
    foreach($associationsFields as $aField){
      if(strpos($aField, '_translation') !== false){
        $field = str_replace($this->$model->table().'_', '', str_replace('_translation', '', $aField));
        $transaltionsFields[] = $field;
      }
    }
    if(empty($transaltionsFields)) return $this->err('No field to translate found');

    if(empty($locales)) return $this->err('Need to pass at least one locale');

    $availableLocales = Configure::read('I18n.languages');

    foreach($locales as $locale){
      if(!in_array($locale, $availableLocales)){
        return $this->err('Locale '.$locale.' not found in available languages');
      }
    }

    $this->loadModel('I18n');
    foreach($locales as $locale){

      $alias = Inflector::camelize($locale);
      $elems = $this->$model->find()
      ->leftJoin([$alias => 'i18n'], [$alias.'.foreign_key = '.$model.'.id', $alias.'.model' => $model, $alias.'.locale' => $locale])
      ->where([$alias.'.id IS NULL'])
      ->toArray();

      if(empty($elems)) return $this->info('All '.$model.' are already translated in '.$locale);

      $entities = [];
      foreach($elems as $elem)
      {
        foreach($transaltionsFields as $field)
        {
          $entities[] = [
            'locale' => $locale,
            'model' => $model,
            'foreign_key' => $elem->id,
            'field' => $field,
            'content' => ($field == 'slug')? $elem->slug : '',
          ];
        }
      }

      $entities = $this->I18n->newEntities($entities);
      $results = $this->I18n->saveMany($entities);
      if(empty($results))
      {
        return $this->err('No translations was added, an error occured!');
      }else
      {
        $this->info(count($results).' translations was added for '.count($elems).' '.$model.' in '.$locale);
      }

    }
  }
}
