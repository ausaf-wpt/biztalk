<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class VideoCollection extends ResourceCollection
{
    // public function with($request)
    // {
    //     return [
    //         'status' =>'success',
    //         'message' => 'Video List'
    //     ];
    // }
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            $this->collection->map(function ($data) {
                return [
                    'id' => $data->id,
                    'title' => $data->video_title,
                    'description' => $data->video_description,
                    'investment_req' => $data->investment_req,
                    'allow_comment' => $data->allow_comment,
                    'user_id' => $data->users->id,
                    'username' => $data->users->username,
                    'user_name' => $data->users->first_name . ' ' . $data->users->last_name,
                    'total_comments' => $data->comments->count(),
                    'urls' => asset('uploads/videos/' . $data->video_name),
                    'video_comments' => new CommentCollection($data->comments),
                    // 'comments_users'=>new UserCollection($data->comments),
                    // 'replies'=>new ReplyCollection($data->comments)
                ];
            }),
            
        ];
    }
   
}