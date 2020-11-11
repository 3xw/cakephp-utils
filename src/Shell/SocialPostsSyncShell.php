<?php
namespace Trois\Utils\Shell;

use Cake\Core\Configure;
use Cake\Console\Shell;

class SocialPostsSyncShell extends Shell
{

  public function main($media, $type, $key, $outputModel = 'SocialPosts', $limit = 10)
  {

    $config = Configure::read('Socials');
    $posts = [];

    $this->out('Loading Posts form '.$key.' on '.$media);

    switch($media){
      case 'instagram':
      if($type == 'account'){
        $feedUrl = 'https://www.instagram.com/'.$key.'/?__a=1';
      }elseif($type == 'search'){
        $feedUrl = 'https://www.instagram.com/explore/tags/'.$key.'/?__a=1';
      }

      $queryPosts = json_decode(file_get_contents($feedUrl));

      if($type == 'account'){
        $datas = $queryPosts->graphql->user->edge_owner_to_timeline_media->edges;
      }elseif($type == 'search'){
        $datas = $queryPosts->graphql->hashtag->edge_hashtag_to_media->edges;
      }

      foreach($datas as $dataKey => $data){
        if($dataKey < $limit){
          $data = $data->node;
          $post = [];
          $post['id'] = $media.$data->id;
          $post['provider'] = $media;
          $post['full_data'] = json_encode($data);
          $post['date'] = ($data->taken_at_timestamp)? date("Y-m-d H:i:s", $data->taken_at_timestamp) : null;
          $post['link'] = 'https://www.instagram.com/p/'.$data->id;
          $post['message'] = $data->edge_media_to_caption->edges[0]->node->text ?? null;

          if($type == 'account'){
            $post['author'] = $queryPosts->graphql->user->full_name;
            $post['attachment'] = ($data->is_video)? $data->video_url : $data->display_url;
          }elseif($type == 'search'){
            $post['author'] = $data->owner->username;
            $post['attachment'] = ($data->is_video)? $data->video_url : $data->thumbnail_src;
          }

          $posts[] = $post;
        }
      }
      break;
    }

    if(empty($posts)) $this->abort('No posts found');

    $model = $this->loadModel($outputModel);
    if(!$model) $this->abort('Model not found');

    $entities = $model->newEntities($posts);
    if($model->saveMany($entities)){
      $this->out('New Posts saved');
    }else{
      $this->abort('No Posts saved');
    }

  }

}
