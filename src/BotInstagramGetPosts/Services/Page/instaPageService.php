<?php

namespace BotInstagramGetPosts\Services\Page;

use BotInstagramGetPosts\Services\Request\requestServer;
use BotInstagramGetPosts\Services\Graphql\instaGraphqlService;

class instaPageService
{
    
    private $response;
    private $end_cursor = false;
    private $hash = '69cba40317214236af40e7efa697781d';
    private $data = [];
    private $userPageId;
    private $position = 0;
    private $limit = false;

    public function __construct()
    {
        $this->Request = new requestServer();
        $this->InstaGraphql = new instaGraphqlService();
    }

    public function requestPage($url, $sessionId, $limit = false)
    {
        $this->limit = $limit;
        $ref = 'https://www.instagram.com/';
        $this->prepareCokys('sessionid=' . $sessionId);
        $response = $this->Request->request($url, $ref, 'GET');
        $this->getSharedData($response);
        $this->getDatas();
        $this->InstaGraphql->setData($this->data);
        $this->getEndCursorPage(end($this->response));
        $this->getNextResponse($sessionId);
        $this->data = $this->InstaGraphql->getData();
        $this->data = $this->getIsLimit();
        return $this->data;
    }

    private function getIsLimit()
    {
        if($this->limit){
            if($this->limit <= count($this->data)){
                $this->data = array_slice($this->data, 0, $this->limit);
            }
            
        }
        return $this->data;
    }

    private function getNextResponse($sessionId)
    {
        $this->InstaGraphql->setPosition($this->position);
        while (true) {
            if($this->limit){
                if($this->limit <= count($this->InstaGraphql->getData())){
                    break;
                }
            }

            $this->prepareCokys('sessionid=' . $sessionId);
            $url = 'https://www.instagram.com/graphql/query/?query_hash=' . $this->hash . '&variables={"id":"'.$this->userPageId.'","first":12,"after":"' . $this->end_cursor . '"}';
            $response = $this->Request->request($url, 'https://www.instagram.com/', 'GET');
            $this->InstaGraphql->run($response);
            $this->end_cursor = $this->InstaGraphql->getEndCursor();
            sleep(10);
            
            if ($this->end_cursor === false) {
                break;
            }
           
        }
        
    }

    private function getDatas()
    {
       foreach($this->response as $data){
           
           if(isset($data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'])){
               foreach($data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['edges'] as $item){
                $this->setImage($item);
                $this->setDescription($item);
                $this->setVideos($item);
                $this->setLikes($item);

                $this->position++;
               }
           }
       }
    }

    private function setLikes($item)
    {
        if (isset($item['node']['edge_media_preview_like']['count'])) {
            $this->data[$this->position]['likes'] = $item['node']['edge_media_preview_like']['count'];
        } else {
            $this->data[$this->position]['likes'] = 0;
        }
        
    }

    private function setVideos($item)
    {
        if (isset($item['node']['video_url'])) {
            $this->data[$this->position]['videos'][] = $item['node']['video_url'];
        }
    }

    private function setDescription($item)
    {
        if (isset($item['node']['edge_media_to_caption']['edges'][0]['node']['text'])) {
            $this->data[$this->position]['description'] = $item['node']['edge_media_to_caption']['edges'][0]['node']['text'];
        } else {
            $this->data[$this->position]['description'] = '';
        }
    }
    
    private function setImage($item)
    {
        if (isset($item['node']['display_url'])) {
            $this->data[$this->position]['image'][] = $item['node']['display_url'];
        }
    }


    private function getEndCursorPage($response)
    {
        try {
            $this->end_cursor = $response['entry_data']['ProfilePage'][0]['graphql']['user']['edge_owner_to_timeline_media']['page_info']['end_cursor'];
        } catch (\Exception $e) {
            $this->end_cursor = false;
        }
    }



    private function getSharedData($html)
    {
        $pattern = '#window\._sharedData\s*=\s*(.*?)\s*;\s*</script>#is';
        preg_match($pattern, $html, $match);
        $this->response[] = json_decode($match[1], true);
        $this->setUserPageId(end($this->response));
    }

    private function setUserPageId($response)
    {
        $this->userPageId = $response['entry_data']['ProfilePage'][0]['graphql']['user']['id'];
    }



    private function prepareCokys(string $cokis)
    {
        $this->Request->setcookie($cokis);
    }
}
