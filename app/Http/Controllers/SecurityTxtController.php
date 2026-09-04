<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SecurityTxtController extends Controller
{
    public function __invoke(): Response
    {
        $contact = trim((string) config('mail.contact_to'));

        abort_if($contact === '', 503);

        $expires = now()
            ->addYear()
            ->startOfDay()
            ->utc()
            ->format('Y-m-d\TH:i:s\Z');

        return response(
            "Contact: mailto:$contact\n" .
            "Preferred-Languages: pl, en\n" .
            "Expires: $expires\n",
            200,
            ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }
}
