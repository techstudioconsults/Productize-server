<?php

namespace App\Http\Controllers;

use App\Models\Faqs;
use Illuminate\Http\Request;

class FaqsController extends Controller
{
    // Show all Faqs
    public function index(){
         $faqs = Faqs::all();
         return response()->json($faqs);
    }

    public function store(Request $request){
      $data = $request-> validate([
        'title' => 'required',
        'question' => 'required',
        'answer' => 'required'
      ]);

     $newFaq = Faqs::create($data);
     return response()->json($newFaq, 201);
    }
}
