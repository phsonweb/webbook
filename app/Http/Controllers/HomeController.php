<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::orderBy('created_at', 'DESC');
        if (!empty($request->keyword)) {
            $books->where('title', 'like', '%' . $request->keyword . '%');
        }
        $books = $books->where('status', 1)->paginate(8);
        return view('home', [
            'books' => $books
        ]);
    }

    //This method will show detail book
    public function detail($id)
    {
        //Tim kiem thong tin sach 
        $book = Book::with(['reviews.user', 'reviews' => function ($query) {
            $query->where('status', 1);
        }])->findOrFail($id);

        //Neu sach co trang thai bang 0(block) thi thong bao 404
        if ($book->status == 0) {
            abort(404);
        }

        //Random ngau nhien 3 sach trang thai 1(active)
        $relatedBooks = Book::where('status', 1)->take(3)->where('id', '!=', $id)->inRandomOrder()->get();

        return view('book-detail', [
            'book' => $book,
            'relatedBooks' => $relatedBooks
        ]);
    }

    public function saveReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'review' => 'required|min:10',
            'rating' => 'required'

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        //Apply condition here
        $countReview = Review::where('user_id', Auth::user()->id)->where('book_id', $request->book_id)->count();
        if ($countReview > 0) {
            session()->flash('error', 'you alredly submitted review');
            return response()->json([
                'status' => true,

            ]);
        }


        $review = new Review();
        $review->review = $request->review;
        $review->rating = $request->rating;
        $review->user_id = Auth::user()->id;
        $review->book_id = $request->book_id;
        $review->save();

        session()->flash('success', 'Success submit successfuly');
        return response()->json([
            'status' => true,

        ]);
    }
}
