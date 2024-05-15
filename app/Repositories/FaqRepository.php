<?php

namespace App\Repositories;



use App\Models\Faq;


class FaqRepository
{

    public function create(array $array)
    {
        return Faq::create($array);
    }


    public function find()
    {
        return Faq::all();
    }

    public function getById($id)
    {
        return Faq::findOrFail($id);
    }

    public function update($id, array $array)
    {
        $faq = Faq::findOrFail($id);
        $faq->update($array);
        return $faq;
    }

    public function delete($id)
    {
        $faq = Faq::findOrFail($id);
        $faq->delete();
    }
}
