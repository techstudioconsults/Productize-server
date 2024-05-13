<?php

namespace App\Repositories;



use App\Models\Community;


class CommunityRepository
{

    public function create(array $array)
    {
        return Community::create($array);
    }


    public function getAll()
    {
        return Community::all();
    }

    // public function getById($id)
    // {
    //     return Community::findOrFail($id);
    // }

}
