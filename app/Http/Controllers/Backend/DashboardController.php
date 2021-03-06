<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Hashtag;
use App\Models\User;
use App\Models\Video;
use App\Models\CommunityGuideline;
use DataTables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        $user_count = User::whereNot('id',0)->doesntHave('roles')->count();
        $video_count = Video::count();
        $like_count = \DB::table('likeable_likes')->where('likeable_type', 'App\Models\Video')->count();
        $hashtag_count = Hashtag::count();
        $comment_count = Comment::count();
        $recent_users = User::whereNot('id',0)->doesntHave('roles')->latest()->take(5)->get();
        // dd($recent_users);
        return view('Backend.pages.index', compact('user_count', 'video_count', 'like_count', 'comment_count', 'recent_users','hashtag_count'));

    }
    public function userindex()
    {
        return view('Backend.pages.user-index');
    }
    public function userdata(Request $request)
    {
        if ($request->ajax()) {

            $data = User::whereNot('id',0)->doesntHave('roles')->latest()->get();
            return DataTables::of($data)
            // ->addIndexColumn()

                ->addColumn('user', function ($row) {
                    if ($row->profile_image == null) {
                        $user = "<img src='" . asset('assets/images/faces/face1.jpg') . "' style='border-radius: 5px;' alt='image' />";
                        return $user;
                    } else {
                        $user = "<img src='" . asset('uploads/avtars/' . $row->profile_image) . "' style='border-radius: 5px;' alt='image' />";
                        return $user;
                    }

                })
                ->addColumn('name', function ($row) {
                    $name = $row->first_name . ' ' . $row->last_name;
                    return $name;
                })
                ->addColumn('username', function ($row) {
                    $username = $row->username;
                    return $username;
                })
                ->addColumn('phone', function ($row) {
                    if ($row->phone_no == null) {
                        return "N/A";
                    } else {
                        $phoneno = $row->phone_no;
                        return $phoneno;
                    }

                })
                ->addColumn('email', function ($row) {

                    if ($row->email == null) {
                        return "N/A";
                    } else {
                        $email = $row->email;
                        return $email;
                    }

                })
                ->addColumn('status', function ($row) {
                    if ($row->is_verified == "pending") {
                        $status = "<label class='badge badge-warning'>Pending</label>";
                    } elseif ($row->is_verified == "active") {
                        $status = "<label class='badge badge-success'>Active</label>";
                    } else {
                        $status = "<label class='badge badge-danger'>" . $row->is_verified . "</label>";
                    }

                    return $status;
                })
                ->addColumn('joineddate', function ($row) {
                    $joindate = $row->created_at->format('d/m/Y');
                    return $joindate;

                })

                ->addColumn('action', function ($row) {

                    return $action = "<a class='btn btn-warning btn-icon' data-bs-toggle='modal'
                    onclick='viewdetails(event.target)' data-bs-target='#exampleModal' data-id='" . $row->id . "'>
                    View </a> <a class='btn btn-primary btn-icon ' href='" . route('user.edit', ['id' => $row->id]) . "'>
                    Edit <i class='ti-pencil-alt btn-icon-append icons-table'></i></a>
                    <a class='btn btn-danger btn-icon' id='delete' onclick='deleteUser(this)' data-id='" . $row->id . "'>
                    Delete <i class='ti-trash btn-icon-append icons-table'></i></a>
                    
                    ";

                })

                ->rawColumns(['user', 'name', 'username', 'phone', 'email', 'status', 'joineddate', 'action'])
                ->make(true);

        }
    }
    public function useredit($id)
    {
        $user = User::find($id);
        return view('Backend.pages.edit-user', compact('user'));
    }
    public function userdetails($id)
    {
        $user = User::find($id);

        $user_videos = array();
        foreach ($user->videos as $video) {
            $user_videos[] = $video->id;
        }
        $like = DB::table('likeable_likes')->where('likeable_type', 'App\Models\Video')->whereIn('likeable_id', $user_videos)->count();

        $user_details = [
            'country' => $user->country != null ? $user->country : 'N/A',
            'dob' => $user->dob != null ? $user->dob : 'N/A',
            'gender' => $user->gender != null ? $user->gender : 'N/A',
            'isaccount_public' => $user->isaccount_public == 1 ? 'Yes' : 'No',
            'total_like_received' => $like,
            'followers_count' => $user->approvedFollowers()->count(),
            'followings_count' => $user->approvedFollowings()->count(),
            'phone'=>$user->phone_no  != null ? $user->phone_no : 'N/A'
        ];
        return response()->json(['user_details' => $user_details], 200);
    }
    public function userupdate(Request $request)
    {
        // if ($request->get('first_name')!=null||$request->get('last_name')!=null || $request->get('email')!=null||$request->get('username')!=null||$request->get('phone_no')!=null) {
        $user = User::find($request->id)->update([
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'username' => $request->get('username'),
            'email' => $request->get('email'),
            'phone_no' => $request->get('phone'),
        ]);
        // }

        return redirect()->route('user.index')->with('success', 'User details updated');
    }
    public function useremail(Request $request)
    {
        if ($request->input('email')) {
            $email = $request->input('email');
            $isExists = User::whereNot('id',0)->where('email', $email)->first();

            if ($isExists) {
                return response()->json(array("exists" => true));
            } else {
                return response()->json(array("exists" => false));
            }

        } elseif ($request->input('phone')) {
            $phone = $request->input('phone');
            $isExists = User::whereNot('id',0)->where('phone_no', $phone)->first();

            if ($isExists) {
                return response()->json(array("exists" => true));
            } else {
                return response()->json(array("exists" => false));
            }
        } elseif ($request->input('username')) {
            $username = $request->input('username');
            $isExists = User::whereNot('id',0)->where('username', $username)->first();

            if ($isExists) {
                return response()->json(array("exists" => true));
            } else {
                return response()->json(array("exists" => false));
            }
        }

    }
    public function userstatus(Request $request)
    {
        // dd($request->get('ban_status'));
        $user = User::find($request->user_id);

        $user->update([
            'is_verified' => $request->get('status'),
        ]);

        if ($request->get('ban_status') == 'yes') {
            $user->ban_time = now();
            $user->update();
        } elseif ($request->get('ban_status') == 'no') {
            $user->ban_time = null;
            $user->update();
        }

        return redirect()->route('user.index')->with('success', 'User status updated');
    }
    public function userdelete(Request $request)
    {
        User::find($request->get('id'))->delete();
        return response()->json(200);
    }
    public function videos()
    {
        return view('Backend.pages.video-index');
    }
    public function videosdata(Request $request)
    {
        if ($request->ajax()) {
            $data = Video::latest()->get();
            return DataTables::of($data)
            // ->addIndexColumn()
                ->addColumn('video_title', function ($row) {
                    $title = $row->video_title;
                    return $title;
                })
                ->addColumn('video_description', function ($row) {
                    $video_description = $row->video_description;
                    return $video_description;
                })
                ->addColumn('video', function ($row) {
                    if ($row->video_name == null) {
                        $video = "N/A";
                        return $video;
                    } else {
                        if ($row->video_poster != null) {
                            $video = " <video
                            id='my-video'
                            class='video-js vjs-theme-city'
                            controls
                            preload='auto'
                            width='640'
                            height='264'
                            poster='" . asset('uploads/thumbnail/' . $row->video_poster) . "'
                            data-setup='{}'>
                            <source src='" . asset('uploads/videos/' . $row->video_name) . "' type='video/mp4' />

                          </video>
                        ";
                        } else {
                            $video = " <video
                            id='my-video'
                            class='video-js vjs-theme-city'
                            controls
                            preload='auto'
                            width='640'
                            height='264'
                            poster='" . asset('uploads/thumbnail/Poster.png') . "'
                            data-setup='{}'>
                            <source src='" . asset('uploads/videos/' . $row->video_name) . "' type='video/mp4' />

                          </video>
                        ";
                        }

                        return $video;
                    }

                })
                ->addColumn('investment_req', function ($row) {
                    if ($row->investment_req == 0) {
                        $investment_req = "No";
                    } else {
                        $investment_req = "Yes";
                    }

                    return $investment_req;
                })
                ->addColumn('video_category', function ($row) {
                    if ($row->video_category != null) {
                        $video_category = $row->category != null ? $row->category->price_range : 'N/A';

                    } else {
                        $video_category = "N/A";
                    }

                    return $video_category;
                })
                ->addColumn('status', function ($row) {
                    if ($row->is_approved == 0) {
                        $status = "<label class='badge badge-warning'>No</label>";
                    } else {
                        $status = "<label class='badge badge-success'>Yes</label>";
                    }

                    return $status;
                })
                ->addColumn('flagged_video', function ($row) {
                    if ($row->is_flagged == 0) {
                        $status = "<label class='badge badge-warning'>No</label>";
                    } elseif ($row->is_flagged == 1) {
                        $status = "<label class='badge badge-success'>Yes</label>";
                    }

                    return $status;
                })
                ->addColumn('user', function ($row) {
                    if ($row->users->username == null) {
                        $user = $row->users->first_name . ' ' . $row->users->last_name;
                    } else {
                        $user = '@' . $row->users->username;
                    }

                    return $user;
                })
                ->addColumn('created_at', function ($row) {
                    $created_at = $row->created_at->format('d/m/Y');
                    return $created_at;

                })
                ->addColumn('action', function ($row) {

                    return $action = "<a class='btn btn-primary btn-icon' href='" . route('video.edit', ['id' => $row->id]) . "'>
                    Edit <i class='ti-pencil-alt btn-icon-append icons-table'></i></a>
                    <a class='btn btn-danger btn-icon' data-bs-toggle='modal'
                    onclick='viewdetails(event.target)' data-bs-target='#exampleModal' data-id='" . $row->id . "'>
                    View </a>";

                })
                ->rawColumns(['video_title', 'video_description', 'video', 'investment_req', 'video_category', 'status', 'flagged_video', 'user', 'created_at', 'action'])
                ->make(true);

        }

    }
    public function videodetails($id)
    {
        $video = Video::where('id', $id)->with('allcomments')->first();
        $video_details = [
            'investment_req' => $video->investment_req == 1 ? 'Yes' : 'No',
            'category' => $video->category != null ? $video->category->price_range : 'N/A',
            'is_approved' => $video->is_approved == 1 ? 'Yes' : 'No',
            'is_flagged' => $video->is_flagged == 1 ? 'Yes' : 'No',
            'posted_at' => $video->created_at->format('d/m/Y'),
            'total_likes' => $video->like_count,
            'total_comments' => $video->allcomments->count(),
            'total_views' => $video->view->count(),
        ];
        return response()->json(['video_details' => $video_details], 200);
    }
    public function videoedit($id)
    {
        $video = Video::find($id);
        return view('Backend.pages.edit-video', compact('video'));
    }
    public function videostatus(Request $request)
    {
        $video = Video::find($request->video_id);
        $video->is_approved = $request->get('is_approved');
        $video->update();
        return redirect()->route('video.index')->with('success', 'Video status updated');
    }
    public function banners()
    {
        $banners = Banner::first();
        return view('Backend.pages.banner', compact('banners'));
    }
    public function bannerstore(Request $request)
    {

        if (request()->hasFile('banners')) {
            $arrbanners = array();
            $arrbanners = request()->file('banners');
            // dd($arrbanners);
            foreach ($arrbanners as $banner) {
                $banners_name = rand(0000, 9999) . '_' . $banner->getClientOriginalName();
                $img_data = file_get_contents($banner);
                // dd($file);
                Storage::disk('public')->put('banners/' . $banners_name, $img_data);
                // $banner->move(public_path().'/uploads/banners/', $banners_name);
                // $banner->store('public/uploads/banners/'. $banners_name, '');
                $data[] = $banners_name;
            }
            $banner = Banner::first();
            $banner->image_name = $data;
            $banner->save();

        }

        return redirect()->back()->with('success', 'Banners updated');
    }
    public function hashtags()
    {
        return view('Backend.pages.hashtag');
    }
    public function hashtagsdata(Request $request)
    {
        if ($request->ajax()) {
            $data = Hashtag::orderby('views', 'desc')->latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('hashtag_title', function ($row) {
                    $title = '#' . $row->name;
                    return $title;
                })
                ->addColumn('total_videos', function ($row) {
                    $total_videos = $row->videos->count();
                    return $total_videos;
                })
                ->addColumn('total_views', function ($row) {
                    $total_views = $row->views;
                    return $total_views;
                })
            // ->addColumn('action', function ($row) {

            //     return $action = "<a class='btn btn-primary btn-icon' href='" . route('video.edit', ['id' => $row->id]) . "'>
            //     Edit <i class='ti-pencil-alt btn-icon-append icons-table'></i></a>";

            // })

                ->rawColumns(['hashtag_title', 'total_videos', 'total_views'])
                ->make(true);

        }

    }
    public function category()
    {
        return view('Backend.pages.category-index');
    }
    public function categoryStore(Request $request)
    {
        $category = new Category;
        $category->price_range = $request->get('category');
        $category->save();

        return redirect()->back()->with('success', 'Category Added');
    }
    public function categoryData(Request $request)
    {
        if ($request->ajax()) {
            $data = Category::latest()->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('name', function ($row) {
                    $title = $row->price_range;
                    return $title;
                })
                ->addColumn('total_videos', function ($row) {
                    $totalvideo = $row->videos->count();
                    return $totalvideo;
                })
                ->addColumn('action', function ($row) {

                    return $action = "<a class='btn btn-primary btn-icon' href='" . route('category.edit', ['id' => $row->id]) . "'>
                    Edit <i class='ti-pencil-alt btn-icon-append icons-table'></i></a>
                    <a class='btn btn-danger btn-icon' id='delete' onclick='deletecategory(this)' data-id='" . $row->id . "'>
                    Delete <i class='ti-trash btn-icon-append icons-table'></i></a>
                    ";

                })

                ->rawColumns(['name', 'total_videos', 'action'])
                ->make(true);

        }

    }
    public function categoryedit($id)
    {
        $category = Category::find($id);

        return view('Backend.pages.edit-category', compact('category'));
    }
    public function categoryUpdate(Request $request)
    {
        $category = Category::find($request->get('category_id'));
        $category->price_range = $request->get('category');
        $category->update();

        return redirect()->route('category.index')->with('success', 'Category updated');

    }
    public function categorydelete(Request $request)
    {
        Category::find($request->get('id'))->delete();
        return response()->json(200);
    }
    public function categoryDuplicate(Request $request)
    {
        $categoryexist = Category::where('price_range', $request->get('category'))->first();
        if ($categoryexist) {
            return response()->json(array("exists" => true));
        } else {
            return response()->json(array("exists" => false));
        }
    }
    public function community_guidelines()
    {
       $community =  CommunityGuideline::where('id',1)->get();
    
        return view('Backend.pages.community-guidelines', compact('community'));
    }
    public function Editcommunity_guidelines(Request $request,$id)
    {
        $cg = CommunityGuideline::find($id);
        return view('Backend.pages.edit-community', compact('cg'));
    }
    public function Updatecommunity_guidelines(Request $request)
    {
        $cg = CommunityGuideline::find($request->get('cg_id'));
        $cg->tos = $request->get('tos');
        $cg->update();
        return redirect()->route('community.index')->with('success', 'Community Guidelines updated');
    }
}
