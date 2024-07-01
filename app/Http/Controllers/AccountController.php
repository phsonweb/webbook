<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use App\Models\Review;

class AccountController extends Controller
{
    // This methoad will show register page
    public function register()
    {
        return view('account.register');
    }

    public function processRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:5',
            'password_confirmation' => 'required',

        ]);
        if ($validator->fails()) {
            return redirect()->route('account.register')->withInput()->withErrors($validator);
        }

        // Now register user

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('account.login')->with('success', 'You have register sucessfully .');
    }

    public function login()
    {
        return view('account.login');
    }

    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'email' => 'required|email',
            'password' => 'required',


        ]);
        if ($validator->fails()) {
            return redirect()->route('account.login')->withInput()->withErrors($validator);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            return redirect()->route('account.profile');
        } else {
            return redirect()->route('account.login')->with('success', 'Either email/password incorrect .');
        }
    }

    //Ham show user profile
    public function profile()
    {
        $user = User::find(Auth::user()->id);
        // dd($user);
        return view('account.profile', [
            'user' => $user
        ]);
    }

    //Ham update user profile
    public function updateProfile(Request $request)
    {
        $rules = [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email' . Auth::user()->id . ',id',
        ];
        if (!empty($request->image)) {
            $rules['image'] = 'image';
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->route('account.profile')->withInput()->withErrors($validator);
        }
        $user = User::find(Auth::user()->id);
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        //Upload image
        if (!empty($request->image)) {
            //Delete old image
            File::delete(public_path('uploads/profile/' . $user->image));
            File::delete(public_path('uploads/profile/thumb/' . $user->image));

            //Save new image
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time() . '.' . $ext; // 121212.jpg
            $image->move(public_path('uploads/profile'), $imageName);
            $user->image = $imageName;
            $user->save();

            //Resize image
            $manager = new ImageManager(new Driver());
            $img = $manager->read(public_path('uploads/profile/' . $imageName));
            $img->resize(990);
            $img->save(public_path('uploads/profile/thumb/' . $imageName));
        }


        //Quay lai trang profile va cho ra ket qua
        return redirect()->route('account.profile')->with('success', 'You have update sucessfully .');
    }
    //Ham logout
    public function logout()
    {
        Auth::logout();
        return redirect()->route('account.login');
    }

    public function myReviews(Request $request)
    {
        $reviews = Review::with('book')->where('user_id', Auth::user()->id);
        $reviews = $reviews->orderBy('created_at', 'DESC');
        if (!empty($request->keyword)) {
            $reviews = $reviews->where('review', 'like', '%' . $request->keyword . '%');
        }
        $reviews = $reviews->paginate(10);

        return view('account.my-reviews.my-reviews', [
            'reviews' => $reviews
        ]);
    }

    public function editReview($id)
    {
        $review = Review::where([
            'id' => $id,
            'user_id' =>  Auth::user()->id
        ])->first();

        return view('account.my-reviews.edit-reviews', [
            'review' => $review
        ]);
    }

    public function updateReview($id, Request $request)
    {
        $review = Review::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'review' => 'required',
            'rating' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->route('account.my-reviews.editReview', $id)->withInput()->withErrors($validator);
        }

        $review->review = $request->review;
        $review->rating = $request->rating;
        $review->save();

        session()->flash('success', 'Review updated');
        return redirect()->route('account.myReviews');
    }

    public function deleteReview(Request $request)
    {
        $id = $request->id;
        $review = Review::find($id);
        if ($review == null) {
            session()->flash('error', 'Review not found');
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Review not found'
                ]
            );
        }

        $review->delete();
        session()->flash('success', 'Review deleted');
        return response()->json(
            [
                'status' => true,
                'message' => 'Review deleted'
            ]
        );
    }
}
