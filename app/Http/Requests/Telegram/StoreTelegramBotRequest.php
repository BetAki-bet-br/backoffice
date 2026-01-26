<?php

declare(strict_types=1);

namespace App\Http\Requests\Telegram;

use Illuminate\Foundation\Http\FormRequest;

class StoreTelegramBotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('telegram_bot.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'bot_token' => 'required|string|unique:telegram_bots',
            'username' => 'required|string|unique:telegram_bots|regex:/^[a-zA-Z0-9_]{5,32}$/',
            'description' => 'nullable|string|max:1000',
            'api_url' => 'required|url',
            'api_key' => 'required|string|min:10',
            'portal_id' => 'required|integer|min:1',
            'group_chat_id' => 'nullable|string',
            'group_invite_link' => 'nullable|url',
            'register_url' => 'nullable|url',
            'debug_mode' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do bot é obrigatório',
            'bot_token.required' => 'O token do bot é obrigatório',
            'bot_token.unique' => 'Este token de bot já está registrado',
            'username.required' => 'O usuário do bot é obrigatório',
            'username.unique' => 'Este usuário de bot já está registrado',
            'username.regex' => 'O usuário deve ter entre 5 e 32 caracteres, apenas letras, números e underscore',
            'api_url.required' => 'A URL da API é obrigatória',
            'api_url.url' => 'A URL da API deve ser um URL válido',
            'api_key.required' => 'A chave da API é obrigatória',
            'portal_id.required' => 'O ID do portal é obrigatório',
        ];
    }
}
