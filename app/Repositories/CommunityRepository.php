<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 09-05-2024
 */


namespace App\Repositories;

use App\Models\Community;


class CommunityRepository
{

    public function create(array $array)
    {
        return Community::create($array);
    }


    public function findAll()
    {
        return Community::all();
    }

    // public function getById($id)
    // {
    //     return Community::findOrFail($id);
    // }

}
