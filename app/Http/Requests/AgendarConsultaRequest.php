<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgendarConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tratamento_id' => ['required', 'integer', 'exists:tratamentos,id,ativo,1'],
            'nome_cliente' => ['required', 'string', 'max:150'],
            'telefone_cliente' => ['required', 'string', 'max:30', 'regex:/^[\d\s()+\-]{10,30}$/'],
            'email_cliente' => ['required', 'email', 'max:190'],
            'data' => ['required', 'date_format:Y-m-d'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'consentimento' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tratamento_id.required' => 'Escolha um tratamento.',
            'tratamento_id.exists' => 'Tratamento inválido.',
            'nome_cliente.required' => 'Informe seu nome.',
            'telefone_cliente.required' => 'Informe seu WhatsApp.',
            'telefone_cliente.regex' => 'Informe um WhatsApp válido, com DDD.',
            'email_cliente.required' => 'Informe um e-mail para contato.',
            'email_cliente.email' => 'Informe um e-mail válido.',
            'data.required' => 'Escolha uma data.',
            'hora_inicio.required' => 'Escolha um horário.',
            'consentimento.accepted' => 'É necessário autorizar o contato para enviar a solicitação.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'tratamento_id' => 'tratamento',
            'nome_cliente' => 'nome',
            'telefone_cliente' => 'WhatsApp',
            'email_cliente' => 'e-mail',
            'data' => 'data',
            'hora_inicio' => 'horário',
        ];
    }
}
