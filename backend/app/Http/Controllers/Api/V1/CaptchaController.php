<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
        Cache::put('captcha_'.$captchaId, Hash::make(strtoupper($captchaText)), 300);

        // SVG Generator
        $width = 150;
        $height = 50;
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';
        $svg .= '<defs>';
        $svg .= '<filter id="captcha-blur" x="-30%" y="-30%" width="160%" height="160%">';
        $svg .= '<feDropShadow dx="0" dy="0" stdDeviation="1.2" flood-color="#0f766e" flood-opacity="0.3" result="shadow"/>';
        $svg .= '<feGaussianBlur in="SourceGraphic" stdDeviation="0.85" result="blurred"/>';
        $svg .= '<feMerge>';
        $svg .= '<feMergeNode in="shadow"/>';
        $svg .= '<feMergeNode in="blurred"/>';
        $svg .= '</feMerge>';
        $svg .= '</filter>';
        $svg .= '</defs>';
        $svg .= '<rect width="'.$width.'" height="'.$height.'" fill="#f0fdf4"/>'; // light green bg

        // Tambahkan garis pengganggu (tetap tajam)
        for ($i = 0; $i < 5; $i++) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $x2 = random_int(0, $width);
            $y2 = random_int(0, $height);
            $svg .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="#a7f3d0" stroke-width="2"/>';
        }

        // Tambahkan teks karakter dengan filter blur dan distorsi visual ringan
        // Jarak x diatur agar karakter terdistribusi rapi dan tidak tertutup tombol refresh di sebelah kanan
        $svg .= '<g filter="url(#captcha-blur)">';
        $startX = 18;
        $stepX = 21;
        for ($i = 0; $i < $length; $i++) {
            $char = $captchaText[$i];
            $x = $startX + ($i * $stepX) + random_int(-2, 2);
            $y = 27 + random_int(-3, 3);
            $angle = random_int(-8, 8);
            $svg .= '<text x="'.$x.'" y="'.$y.'" dominant-baseline="middle" text-anchor="middle" font-family="monospace" font-size="24" font-weight="bold" fill="#0f766e" font-style="italic" transform="rotate('.$angle.', '.$x.', '.$y.')">';
            $svg .= htmlspecialchars($char, ENT_XML1, 'UTF-8');
            $svg .= '</text>';
        }
        $svg .= '</g>';
        $svg .= '</svg>';

        $base64Svg = 'data:image/svg+xml;base64,'.base64_encode($svg);

        return response()->json([
            'data' => [
                'captcha_id' => $captchaId,
                'captcha_image' => $base64Svg,
                'expires_in' => 300,
            ],
        ]);
    }
}
