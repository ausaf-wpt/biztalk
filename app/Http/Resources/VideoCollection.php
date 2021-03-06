<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

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
                    'isFollowing'=>Auth::user()->isFollowing(User::find($data->users->id)),
                    'user_name' => $data->users->first_name . ' ' . $data->users->last_name,
                    'profile_image' => asset('uploads/avtars/' . $data->users->profile_image),
                    'total_comments' => $data->allcomments->count(),
                    'total_likes' => $data->likeCount,
                    'isVideoLiked'=>$data->liked(Auth::user()->id),
                    'isVideoPublic'=>$data->is_active,
                    'created_at'=>$data->created_at,
                    'video_category'=>$data->category != null ? $data->category->price_range : null,
                    'urls' => asset('uploads/videos/' . $data->video_name),
                    'thumbnail'=> asset('uploads/thumbnail/' . $data->video_poster),
                    'views_count'=>$data->view->count(),
                    'video_comments' => new CommentCollection($data->comments),
                    
                    // 'replies'=>new ReplyCollection($data->comments)
                ];
            }),

        ];
    }

}
