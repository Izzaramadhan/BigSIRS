<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class CaptchaController extends Controller
{
    public function generate()
    {
        // Karakter yang tidak membingungkan
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $captchaText = '';
        $length = 5;

        for ($i = 0; $i < $length; $i++) {
            $captchaText .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Simpan hash jawaban di cache selama 5 menit
        $captchaId = (string) Str::uuid();
        Cache::put('captcha_' . $captchaId, Hash::make(strtoupper($captchaText)), 300);

        // SVG Generator
        $width = 150;
        $height = 50;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';
        $svg .= '<rect width="'.$width.'" height="'.$height.'" fill="#f0fdf4"/>'; // light green bg
        
        // Tambahkan garis pengganggu
        for ($i = 0; $i < 5; $i++) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $x2 = random_int(0, $width);
            $y2 = random_int(0, $height);
            $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="#a7f3d0" stroke-width="2"/>';
        }

        // Tambahkan teks
        $svg .= '<text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="monospace" font-size="24" font-weight="bold" fill="#0f766e" letter-spacing="4" font-style="italic">';
        $svg .= $captchaText;
        $svg .= '</text>';
        $svg .= '</svg>';

        $base64Svg = 'data:image/svg+xml;base64,' . base64_encode($svg);

        return response()->json([
            'data' => [
                'captcha_id' => $captchaId,
                'captcha_image' => $base64Svg,
                'expires_in' => 300
            ]
        ]);
    }
}
