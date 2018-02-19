<?php
namespace Trois\Utils\View\Cell;

use Cake\View\Cell;
use Cake\I18n\I18n;
use Cake\Core\Configure;
use Cake\Utility\Inflector;
use Cake\Routing\Router;


class LngSwitchCell extends Cell
{

    /**
     * List of valid options that can be passed into this
     * cell's constructor.
     *
     * @var array
     */
    protected $_validCellOptions = [];

    /**
     * Default display method.
     *
     * @return void
     */
    public function display()
    {

      $urls = [];
      $lngs = Configure::read('I18n.languages');
      $defaultLng = Configure::read('App.defaultLocale');
      $currentLng = I18n::locale();
      $params = $this->request->params;

      $currentModel = Inflector::humanize($params['controller']);

      $hasTradSlug = false;

      $this->loadModel($currentModel);
      $modelAssociation = $this->$currentModel->associations()->keys();

      $slugField = strtolower($currentModel).'_slug_translation';

      if(in_array($slugField, $modelAssociation)){

        $hasTradSlug = true;
        if($defaultLng == $params['lang']){
          $condition = [$currentModel.'.slug' => $params['pass'][0]];
        }else{
          I18n::setLocale($defaultLng);
          $this->loadModel('i18n');
          $currentID = $this->i18n->find()
          ->select('foreign_key')
          ->where([
            'content' => $params['pass'][0],
            'locale' => $currentLng,
            'model' => $currentModel
          ])
          ->first();

          $condition = [$currentModel.'.id' => $currentID->foreign_key];
        }

        $currentItem = $this->$currentModel->find('translations')
        ->where($condition)
        ->first();

      }else{

        $currentItem = $this->$currentModel->find('translations')
        ->where([$currentModel.'.slug' => $params['pass'][0]])
        ->first();

      }

      foreach($lngs as $lng){
        $lngName = explode('_', $lng);
        $active = ($lng == $currentLng)? true : false;
        $urls[$lng] = [
          'name' => $lngName[0],
          'url' => [
            'controller' => $params['controller'],
            'action' => $params['action'],
            'lang' => $lng
          ],
          'active' => $active
        ];


        if($lng == $defaultLng){
          array_push($urls[$lng]['url'], $currentItem->slug);
        }else{
          if($hasTradSlug){
            array_push($urls[$lng]['url'], $currentItem->_translations[$lng]->slug);
          }else{
            array_push($urls[$lng]['url'], $currentItem->slug);
          }
        }

      }

      I18n::setLocale($currentLng);
      $this->set('urls', $urls);


    }
}
