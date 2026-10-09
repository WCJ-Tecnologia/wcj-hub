<?php

/*
|--------------------------------------------------------------------------
| Mensagens de validação em português (B1 T25; NUC-14)
|--------------------------------------------------------------------------
| Valem com APP_LOCALE=pt_BR. A regra que não está aqui cai no inglês do framework
| (APP_FALLBACK_LOCALE): ao usar regra nova numa tela, traduza aqui no mesmo commit. As
| mensagens próprias dos FormRequests e dos Services (CNPJ repetido, carga do Desenvolvedor,
| ANONIMIZAR) continuam valendo por cima destas.
*/

return [

    'accepted' => 'O campo :attribute precisa ser aceito.',
    'after' => 'O campo :attribute precisa ser uma data depois de :date.',
    'after_or_equal' => 'O campo :attribute precisa ser uma data igual ou depois de :date.',
    'array' => 'O campo :attribute precisa ser uma lista.',
    'before' => 'O campo :attribute precisa ser uma data antes de :date.',
    'before_or_equal' => 'O campo :attribute precisa ser uma data igual ou antes de :date.',
    'between' => [
        'array' => 'O campo :attribute precisa ter entre :min e :max itens.',
        'file' => 'O campo :attribute precisa ter entre :min e :max kilobytes.',
        'numeric' => 'O campo :attribute precisa estar entre :min e :max.',
        'string' => 'O campo :attribute precisa ter entre :min e :max caracteres.',
    ],
    'boolean' => 'O campo :attribute precisa ser sim ou não.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'current_password' => 'A senha está incorreta.',
    'date' => 'O campo :attribute precisa ser uma data válida.',
    'date_format' => 'O campo :attribute precisa estar no formato :format.',
    'decimal' => 'O campo :attribute precisa ter :decimal casas decimais.',
    'different' => 'Os campos :attribute e :other precisam ser diferentes.',
    'digits' => 'O campo :attribute precisa ter :digits dígitos.',
    'digits_between' => 'O campo :attribute precisa ter entre :min e :max dígitos.',
    'email' => 'O campo :attribute precisa ser um e-mail válido.',
    'exists' => 'O valor escolhido em :attribute não existe.',
    'filled' => 'O campo :attribute precisa ter um valor.',
    'gt' => [
        'array' => 'O campo :attribute precisa ter mais de :value itens.',
        'file' => 'O campo :attribute precisa ter mais de :value kilobytes.',
        'numeric' => 'O campo :attribute precisa ser maior que :value.',
        'string' => 'O campo :attribute precisa ter mais de :value caracteres.',
    ],
    'gte' => [
        'array' => 'O campo :attribute precisa ter :value itens ou mais.',
        'file' => 'O campo :attribute precisa ter :value kilobytes ou mais.',
        'numeric' => 'O campo :attribute precisa ser maior ou igual a :value.',
        'string' => 'O campo :attribute precisa ter :value caracteres ou mais.',
    ],
    'in' => 'O valor escolhido em :attribute não é válido.',
    'integer' => 'O campo :attribute precisa ser um número inteiro.',
    'lt' => [
        'array' => 'O campo :attribute precisa ter menos de :value itens.',
        'file' => 'O campo :attribute precisa ter menos de :value kilobytes.',
        'numeric' => 'O campo :attribute precisa ser menor que :value.',
        'string' => 'O campo :attribute precisa ter menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'O campo :attribute pode ter no máximo :value itens.',
        'file' => 'O campo :attribute pode ter no máximo :value kilobytes.',
        'numeric' => 'O campo :attribute precisa ser menor ou igual a :value.',
        'string' => 'O campo :attribute pode ter no máximo :value caracteres.',
    ],
    'max' => [
        'array' => 'O campo :attribute pode ter no máximo :max itens.',
        'file' => 'O campo :attribute pode ter no máximo :max kilobytes.',
        'numeric' => 'O campo :attribute pode ser no máximo :max.',
        'string' => 'O campo :attribute pode ter no máximo :max caracteres.',
    ],
    'min' => [
        'array' => 'O campo :attribute precisa ter pelo menos :min itens.',
        'file' => 'O campo :attribute precisa ter pelo menos :min kilobytes.',
        'numeric' => 'O campo :attribute precisa ser pelo menos :min.',
        'string' => 'O campo :attribute precisa ter pelo menos :min caracteres.',
    ],
    'not_in' => 'O valor escolhido em :attribute não é válido.',
    'numeric' => 'O campo :attribute precisa ser um número.',
    'password' => [
        'letters' => 'O campo :attribute precisa ter pelo menos uma letra.',
        'mixed' => 'O campo :attribute precisa ter pelo menos uma letra maiúscula e uma minúscula.',
        'numbers' => 'O campo :attribute precisa ter pelo menos um número.',
        'symbols' => 'O campo :attribute precisa ter pelo menos um símbolo.',
        'uncompromised' => 'Esta :attribute apareceu num vazamento de dados. Escolha outra.',
    ],
    'present' => 'O campo :attribute precisa estar presente.',
    'regex' => 'O formato do campo :attribute não é válido.',
    'required' => 'O campo :attribute é obrigatório.',
    'required_if' => 'O campo :attribute é obrigatório quando :other é :value.',
    'required_unless' => 'O campo :attribute é obrigatório, a menos que :other esteja em :values.',
    'required_with' => 'O campo :attribute é obrigatório quando :values está preenchido.',
    'required_without' => 'O campo :attribute é obrigatório quando :values não está preenchido.',
    'same' => 'Os campos :attribute e :other precisam ser iguais.',
    'size' => [
        'array' => 'O campo :attribute precisa ter :size itens.',
        'file' => 'O campo :attribute precisa ter :size kilobytes.',
        'numeric' => 'O campo :attribute precisa ser :size.',
        'string' => 'O campo :attribute precisa ter :size caracteres.',
    ],
    'string' => 'O campo :attribute precisa ser um texto.',
    'unique' => 'Este :attribute já está em uso.',
    'url' => 'O campo :attribute precisa ser um endereço válido.',
    'uuid' => 'O campo :attribute precisa ser um UUID válido.',

    /*
    | Os nomes dos campos do Núcleo, como a tela os chama (F1 §02). O FormRequest que passa os
    | próprios nomes (ex.: "CNPJ" ou "identificador fiscal", conforme o país) manda sobre estes.
    */
    'attributes' => [
        'name' => 'nome',
        'email' => 'e-mail',
        'role_id' => 'papel',
        'daily_hours' => 'carga diária',
        'ai_write_allowed' => 'IA pode escrever',
        'country' => 'país',
        'document' => 'CNPJ / identificador',
        'phone' => 'telefone',
        'confirmation' => 'confirmação',
        'password' => 'senha',
        'current_password' => 'senha atual',
    ],

];
