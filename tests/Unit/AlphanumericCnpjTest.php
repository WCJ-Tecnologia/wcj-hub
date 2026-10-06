<?php

namespace Tests\Unit;

use App\Rules\AlphanumericCnpj;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** B1 T4: o CNPJ alfanumérico (N5; Clientes AC 1, 2 e o caso da máscara). */
class AlphanumericCnpjTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function validos(): array
    {
        return [
            'exemplo oficial da Receita' => ['12ABC34501DE35'],
            'com máscara' => ['12.ABC.345/01DE-35'],
            'em minúsculas' => ['12abc34501de35'],
            'numérico, o da WCJ' => ['38614817000122'],
        ];
    }

    #[DataProvider('validos')]
    public function test_aceita(string $cnpj): void
    {
        $this->assertNull($this->erro($cnpj));
    }

    /** @return array<string, array{string}> */
    public static function invalidos(): array
    {
        return [
            'primeiro dígito errado' => ['12ABC34501DE45'],
            'segundo dígito errado' => ['12ABC34501DE36'],
            'numérico com dígito errado' => ['38614817000123'],
            'curto' => ['12ABC34501DE3'],
            'longo' => ['12ABC34501DE351'],
            'letra nos dígitos' => ['12ABC34501DE3A'],
            // Passa no cálculo dos dígitos; recusado por ser todo igual.
            'todos iguais' => ['00000000000000'],
            'vazio' => [''],
        ];
    }

    #[DataProvider('invalidos')]
    public function test_recusa_com_a_mensagem_do_campo(string $cnpj): void
    {
        $this->assertSame('Informe um CNPJ válido.', $this->erro($cnpj));
    }

    public function test_normalize_tira_a_mascara_e_poe_maiuscula(): void
    {
        $this->assertSame('12ABC34501DE35', AlphanumericCnpj::normalize('12.abc.345/01de-35'));
        $this->assertSame('38614817000122', AlphanumericCnpj::normalize(' 38.614.817/0001-22 '));
    }

    private function erro(string $cnpj): ?string
    {
        $erro = null;
        (new AlphanumericCnpj)->validate('document', $cnpj, function (string $mensagem) use (&$erro) {
            $erro = $mensagem;
        });

        return $erro;
    }
}
