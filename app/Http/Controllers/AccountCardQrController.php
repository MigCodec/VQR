<?php

namespace App\Http\Controllers;

use App\Models\Card;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Auth;

class AccountCardQrController extends Controller
{
    public function show(Card $card)
    {
        if (! Auth::check()) {
            return redirect()->route('auth.google.redirect');
        }

        abort_unless($card->user_id === Auth::id(), 403);
        abort_unless($card->status === 'active', 404);

        $renderer = new ImageRenderer(
            new RendererStyle(size: 320, margin: 2),
            new SvgImageBackEnd
        );

        $svg = (new Writer($renderer))->writeString($card->public_url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
