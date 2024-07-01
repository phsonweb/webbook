<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\File;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $book = Book::orderBy('created_at', 'DESC');
        if (!empty($request->keyword)) {
            $book->where('title', 'like', '%' . $request->keyword . '%');
        }
        $book = $book->paginate(10);
        return view(
            'books.list',
            [
                'books' => $book
            ]
        );
    }

    //This method will show create book page
    public function create()
    {
        return view('books.create');
    }

    //This method will show  book page indatabase
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|min:5',
            'author' => 'required|min:3',
            'status' => 'required',
        ];

        if (!empty($request->image)) {
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('books.create')->withInput()->withErrors($validator);
        }

        //Save book to DB

        $book = new Book();
        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;
        $book->save();

        //Upload book image here
        if (!empty($request->image)) {

            //Delete old image
            File::delete(public_path('uploads/books/' . $book->image));
            File::delete(public_path('uploads/books/thumb/' . $book->image));

            //Save new image
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time() . '.' . $ext; // 121212.jpg
            $image->move(public_path('uploads/books'), $imageName);
            $book->image = $imageName;
            $book->save();

            //Resize image
            $manager = new ImageManager(new Driver());
            $img = $manager->read(public_path('uploads/books/' . $imageName));
            $img->cover(200, 100);
            $img->save(public_path('uploads/books/thumb/' . $imageName));
        }


        return redirect()->route('books.index')->with('success', 'Book update successfully');
    }

    //This method will edit  book page indatabase
    public function edit($id)
    {
        $book = Book::findOrFail($id);

        return view('books.edit', ['book' => $book]);
    }

    //This method will update  book page indatabase
    public function update($id, Request $request)
    {
        $book = Book::findOrFail($id);
        $rules = [
            'title' => 'required|min:5',
            'author' => 'required|min:3',
            'status' => 'required',
        ];

        if (!empty($request->image)) {
            $rules['image'] = 'image';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->route('books.edit', $book->id)->withInput()->withErrors($validator);
        }

        //Save book to DB


        $book->title = $request->title;
        $book->description = $request->description;
        $book->author = $request->author;
        $book->status = $request->status;
        $book->save();

        //Upload book image here
        if (!empty($request->image)) {

            //Delete old image
            File::delete(public_path('uploads/books/' . $book->image));
            File::delete(public_path('uploads/books/thumb/' . $book->image));

            //Save new image
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = time() . '.' . $ext; // 121212.jpg
            $image->move(public_path('uploads/books'), $imageName);
            $book->image = $imageName;
            $book->save();

            //Resize image
            $manager = new ImageManager(new Driver());
            $img = $manager->read(public_path('uploads/books/' . $imageName));
            $img->cover(200, 100);
            $img->save(public_path('uploads/books/thumb/' . $imageName));
        }


        return redirect()->route('books.index')->with('success', 'Book added successfully');
    }

    //This method will delete  book page indatabase
    public function destroy(Request $request)
    {
        $book = Book::find($request->id);
        if ($book == null) {
            session()->flash('error', 'Book not found');
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Book not found'
                ]
            );
        } else {
            //Delete old image
            File::delete(public_path('uploads/books/' . $book->image));
            File::delete(public_path('uploads/books/thumb/' . $book->image));
            $book->delete();
            session()->flash('success', 'Book deleted');
            return response()->json(
                [
                    'status' => true,
                    'message' => 'Book deleted'
                ]
            );
        }
    }
}
