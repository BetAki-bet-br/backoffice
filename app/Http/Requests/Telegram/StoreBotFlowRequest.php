<?php

declare(strict_types=1);

namespace App\Http\Requests\Telegram;

use Illuminate\Foundation\Http\FormRequest;

class StoreBotFlowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('telegram_bot.update');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'flow_data' => 'required|array',
            'flow_data.*.id' => 'required|string',
            'flow_data.*.type' => 'required|in:message,buttons,input,validation,condition,action',
            'flow_data.*.order' => 'required|integer|min:1',
            'flow_data.*.data' => 'required|array',
            'is_default' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do fluxo é obrigatório',
            'flow_data.required' => 'Os dados do fluxo são obrigatórios',
            'flow_data.*.type.in' => 'Tipo de step inválido',
            'flow_data.*.order.integer' => 'A ordem deve ser um número inteiro',
        ];
    }
}
