<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 13-07-2024
 */

namespace App\Http\Controllers;

use App\Http\Requests\StorecommunityRequest;
use App\Http\Requests\StoreContactUsRequest;
use App\Mail\ContactUsMail;
use App\Mail\ContactUsResponseMail;
use Mail;

class ContactUsController extends Controller
{

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Store a newly created resource in storage.
     *
     * @param  StoreContactusrequest  $request
     *
     * creates a new contact-us info
     */
    public function store(StoreContactUsRequest $request)
    {
        $data = $request->validated();

       // Send mail to team
       Mail::send(new ContactUsMail($data));

       // Send the response mail to the user
       Mail::to($data['email'])->send(new ContactUsResponseMail());

        return response()->json(['Message' => "Your message has been sent."], 200);
    }
}
