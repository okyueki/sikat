<?php

namespace App\Http\Controllers;

use App\Support\DocumentVerificationUrl;
use Illuminate\Http\Request;

class DocumentVerifyRedirectController extends Controller
{
    public function __invoke(Request $request, string $doc, int|string $id)
    {
        return DocumentVerificationUrl::resolveAndRedirect($doc, $id);
    }
}
