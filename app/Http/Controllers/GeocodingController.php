<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GeocodingController extends Controller
{
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = env('TOMTOM_API_KEY', 'h8Fm96GC3rZVi28e6szdI31Rx1X0w0l7');
    }

    public function search(Request $request)
    {
        $query = $request->query('q');
        if (!$query) {
            return response()->json(['error' => 'Parâmetro de consulta é obrigatório'], 400);
        }

        $cacheKey = 'geocode_search_' . md5($query);
        $result = Cache::remember($cacheKey, now()->addHours(24), function () use ($query) {
            $response = Http::get("https://api.tomtom.com/search/2/geocode/{$query}.json", [
                'key' => $this->apiKey,
                'limit' => 1,
                'countrySet' => 'BR',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['results'])) {
                    $result = $data['results'][0];
                    return [
                        'latitude' => $result['position']['lat'],
                        'longitude' => $result['position']['lon'],
                        'address_details' => [
                            'road' => $result['address']['street'] ?? null,
                            'house_number' => $result['address']['streetNumber'] ?? null,
                            'suburb' => $result['address']['municipalitySubdivision'] ?? null,
                            'city' => $result['address']['municipality'] ?? null,
                            'postcode' => $this->formatBrazilianPostalCode($result['address']['postalCode'] ?? null),
                        ],
                    ];
                }
            }
            return null;
        });

        if ($result) {
            return response()->json($result);
        }

        return response()->json(['error' => 'Nenhum resultado encontrado'], 200);
    }

    public function reverse(Request $request)
    {
        $lat = $request->query('lat');
        $lng = $request->query('lng');

        if (empty($lat) || empty($lng)) {
            return response()->json(['error' => 'Latitude e longitude são obrigatórios'], 400);
        }

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return response()->json(['error' => 'Latitude e longitude devem ser numéricos'], 400);
        }

        $lat = floatval($lat);
        $lng = floatval($lng);
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return response()->json(['error' => 'Latitude deve estar entre -90 e 90, longitude deve estar entre -180 e 180'], 400);
        }

        $response = Http::get("https://api.tomtom.com/search/2/reverseGeocode/{$lat},{$lng}.json", [
            'key' => $this->apiKey,
            'limit' => 1,
            'countrySet' => 'BR',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            if (!empty($data['addresses'])) {
                $result = $data['addresses'][0];
                return response()->json([
                    'address_details' => [
                        'road' => $result['address']['street'] ?? null,
                        'house_number' => $result['address']['streetNumber'] ?? null,
                        'suburb' => $result['address']['municipalitySubdivision'] ?? null,
                        'city' => $result['address']['municipality'] ?? null,
                        'postcode' => $this->formatBrazilianPostalCode($result['address']['postalCode'] ?? null),
                    ],
                ]);
            }
        }

        return response()->json(['error' => 'Nenhum resultado encontrado'], 200);
    }

    /**
     * Formata o CEP para o formato completo
     * 
     * @param string|null $postalCode
     * @return string|null
     */
    private function formatBrazilianPostalCode($postalCode)
    {
        if (!$postalCode) {
            return null;
        }

        // Remove caractere que não seja número
        $cleanCode = preg_replace('/\D/', '', $postalCode);
        
        // Se tem 5 dígitos, adiciona -000
        if (strlen($cleanCode) === 5) {
            return $cleanCode . '-000';
        }
        
        // Se tem 8 dígitos, formata
        if (strlen($cleanCode) === 8) {
            return substr($cleanCode, 0, 5) . '-' . substr($cleanCode, 5, 3);
        }
        
        // Se tem entre 6-7 dígitos completa com zeros à direita
        if (strlen($cleanCode) >= 6 && strlen($cleanCode) <= 7) {
            $cleanCode = str_pad($cleanCode, 8, '0', STR_PAD_RIGHT);
            return substr($cleanCode, 0, 5) . '-' . substr($cleanCode, 5, 3);
        }
        
        // se não conseguir formatar
        return $postalCode;
    }
}