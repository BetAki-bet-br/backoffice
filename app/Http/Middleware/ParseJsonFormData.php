<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ParseJsonFormData
{
    /**
     * Handle an incoming request.
     *
     * Processa JSON strings em FormData e as converte em arrays/objetos
     * Exemplo: translations[0][media]='{"desktop":"..."}' → translations[0][media] = [desktop => ...]
     */
    public function handle(Request $request, Closure $next)
    {
        // Processar qualquer requisição POST, PUT ou PATCH com dados
        if (($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch')) 
            && count($request->all()) > 0) {
            $data = $request->all();
            $this->convertJsonStringsToArrays($data);
            $request->merge($data);
        }

        return $next($request);
    }

    /**
     * Recursivamente converte JSON strings em arrays/objetos
     */
    private function convertJsonStringsToArrays(&$data)
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->convertJsonStringsToArrays($value);
            } elseif (is_string($value)) {
                // Tratar strings vazias como arrays vazios para media
                if ($value === '' && strpos($key, 'media') !== false) {
                    $value = [];
                } elseif (strlen($value) > 0 && in_array($value[0], ['{', '['])) {
                    try {
                        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                        $value = $decoded;
                    } catch (\JsonException $e) {
                        // Não é JSON válido, manter como string
                    }
                }
            }
        }
    }
}
