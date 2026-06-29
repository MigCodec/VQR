<?php

namespace App\Http\Controllers;

use App\Models\Card;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class PublicCardQrController extends Controller
{
    public function show(string $shortCode)
    {
        $card = Card::query()
            ->where('short_code', $shortCode)
            ->where('status', 'active')
            ->whereNotNull('user_id')
            ->firstOrFail();

        $renderer = new ImageRenderer(
            new RendererStyle(size: 320, margin: 2),
            new SvgImageBackEnd
        );

        $svg = (new Writer($renderer))->writeString($card->public_url);

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'Content-Disposition' => 'inline; filename="vqr-'.$card->short_code.'.svg"',
        ]);
    }
}
