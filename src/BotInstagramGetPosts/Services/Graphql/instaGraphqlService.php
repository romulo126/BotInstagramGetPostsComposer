<?php

    namespace BotInstagramGetPosts\Services\Graphql;

class instaGraphqlService
{
    private $data =[];
    private $response;
    private $end_cursor = false;
    private $position = 0;

    public function run($response)
    {
        $this->getResponse($response);
        $this->getEndCursorPag(end($this->response));
        $this->setDatas();
    }
    public function setPosition(int $position)
    {
        $this->position = $position;
    }
    public function setData($data)
    {
        $this->data = $data;
    }

    private function getEndCursorPag($response)
    {   
        if(isset($response['data']['user']['edge_owner_to_timeline_media']['page_info']['end_cursor']) && isset($response['data']['user']['edge_owner_to_timeline_media']['page_info']['has_next_page']) && $response['data']['user']['edge_owner_to_timeline_media']['page_info']['has_next_page']){
            $this->end_cursor = $response['data']['user']['edge_owner_to_timeline_media']['page_info']['end_cursor'];
        }else{
            $this->end_cursor = false;
        }
    }

    private function getResponse($response)
    {
        $this->response[] = json_decode($response, true);
    }

    private function setDatas()
    {

        foreach ($this->response as $data) {
            if (isset($data['data']['user']['edge_owner_to_timeline_media']['edges'])) {
                foreach ($data['data']['user']['edge_owner_to_timeline_media']['edges'] as $item) {
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

    public function getData()
    {
        return $this->data;
    }

  

    public function getEndCursor()
    {
        return $this->end_cursor;
    }
}