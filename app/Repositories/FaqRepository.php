<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 09-05-2024
 */

namespace App\Repositories;

use App\Http\Resources\FaqResource;
use App\Models\Faq;


class FaqRepository 
{
    /**
     *  array with params:
     * @param title
     * @param answer
     * @param question
     * 
     */

    public function create(array $array)
    {
        $faq =  Faq::create($array);

        return $faq;
    }


    public function findAll()
    {
      $faq = Faq::all();

      return $faq;
    }

     /**
     *  
     * @param Faq
     */
    public function findById(Faq $faq)
    {
        return Faq::findOrFail($faq);
    }

     /**
     *  array with params:
     * @param title
     * @param answer
     * @param question
     * 
     * @param FaqResource $faqResource
     * 
     */


    public function update(FaqResource $faqResource, array $array): Faq
    {
        $faqArray = $faqResource->toArray(request());
        $request = Faq::where($faqArray)->firstOrFail();
        $request->update($array);
        return $request;
    }

 /**
     *  array with params:
     * @param FaqResource $faqResource
     * 
     */

     public function delete(FaqResource $faqResource): void
     {
         $faqArray = $faqResource->toArray(request());
         $faq = Faq::where($faqArray)->firstOrFail();
         $faq->delete();
     }
}
