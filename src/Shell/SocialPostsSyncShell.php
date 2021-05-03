<?php
namespace Trois\Utils\Shell;

use Cake\Core\Configure;
use Cake\Console\Shell;
use Phpfastcache\Helper\Psr16Adapter;

class SocialPostsSyncShell extends Shell
{

  public function main($media, $type, $key, $outputModel = 'SocialPosts', $limit = 100)
  {

    $config = Configure::read('Api');
    $posts = [];

    $this->out('Loading Posts form '.$key.' on '.$media);

    switch($media){
      case 'instagram':

      if($type == 'account'){
        $instagram = \InstagramScraper\Instagram::withCredentials(new \GuzzleHttp\Client(), $config['instagram']['username'], $config['instagram']['password'], new Psr16Adapter('Files'));
        $instagram->login();
        $instagram->saveSession();
        $datas = $instagram->getMedias($key);

        foreach($datas as $dataKey => $data){
          if($dataKey < $limit){
            $post = [];
            $post['id'] = $media.$data->getId();
            $post['provider'] = $media;
            $post['full_data'] = 'none';
            $post['date'] = ($data->getCreatedTime())? date("Y-m-d H:i:s", $data->getCreatedTime()) : null;
            $post['link'] = 'https://www.instagram.com/p/'.$data->getShortCode();
            $post['message'] = $data->getCaption() ?? null;
            $post['author'] = $data->getOwner()->getUsername();
            $post['attachment'] = ($data->getType() == 'video')? $data->getVideoStandardResolutionUrl() : $data->getImageHighResolutionUrl();

            $posts[] = $post;
          }
        }
      }

      break;
    }

    if(empty($posts)) $this->abort('No posts found');

    $model = $this->loadModel($outputModel);
    if(!$model) $this->abort('Model not found');

    foreach($posts as $key => $post){
      if($model->find()->where([$outputModel.'.id' => $post['id']])->first() != NULL){
        unset($posts[$key]);
      }
    }

    $entities = $model->newEntities($posts);

    if($model->saveMany($entities)){
      $this->out('New Posts saved');
    }else{
      $this->abort('No Posts saved');
    }

  }

}
