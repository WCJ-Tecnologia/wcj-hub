<?php

namespace Tests\Arch\Support;

/**
 * Cobra uma regra de arquitetura com a dívida congelada em tests/Arch/divida.php.
 *
 * Falha com violação nova e também com dívida paga que continua na lista (a lista só
 * diminui). A mensagem é lida pelo normalize.py da skill wcj-testes: a 1ª linha é
 * "<regra> <título>: <n> violações novas." e cada violação é uma linha "- ".
 */
trait VerificaArquitetura
{
    /**
     * @param  list<array{chave: string, texto: string, medida?: int}>  $violacoes
     */
    protected function semViolacoes(string $regra, string $titulo, array $violacoes, string $comoCorrigir): void
    {
        // Instalação num projeto em andamento: WCJ_ARQ_GERAR_DIVIDA=<arquivo.json> grava as
        // violações de hoje em vez de falhar (a skill converte em tests/Arch/divida.php).
        // No CI nunca: seria aprovar tudo com uma variável.
        $gerar = getenv('WCJ_ARQ_GERAR_DIVIDA');
        if (is_string($gerar) && $gerar !== '') {
            if (getenv('CI')) {
                $this->fail('WCJ_ARQ_GERAR_DIVIDA ligado no CI: gerar dívida é passo de instalação, feito na máquina e revisado no PR.');
            }
            self::gravarDivida($gerar, $regra, $violacoes);
            $this->addToAssertionCount(1);

            return;
        }

        $divida = self::dividaDa($regra);
        $novas = [];
        foreach ($violacoes as $violacao) {
            $item = $divida[$violacao['chave']] ?? null;
            $teto = is_array($item) ? ($item['teto'] ?? null) : null;
            if ($item === null) {
                $novas[] = $violacao;
            } elseif ($teto !== null && isset($violacao['medida']) && $violacao['medida'] > $teto) {
                // Na dívida, mas cresceu: a dívida congela o tamanho de hoje, não autoriza piorar.
                $novas[] = ['chave' => $violacao['chave'], 'texto' => "{$violacao['texto']}, acima do teto da dívida ({$teto})"];
            }
        }
        $pagas = array_values(array_diff(array_keys($divida), array_column($violacoes, 'chave')));

        if ($novas === [] && $pagas === []) {
            $this->addToAssertionCount(1);

            return;
        }

        $linhas = [sprintf('%s %s: %d %s.', $regra, $titulo, count($novas), count($novas) === 1 ? 'violação nova' : 'violações novas')];
        foreach ($novas as $violacao) {
            $linhas[] = '- '.$violacao['texto'];
        }
        if ($novas !== []) {
            $linhas[] = 'Como corrigir: '.$comoCorrigir;
        }
        if ($pagas !== []) {
            $linhas[] = "Dívida paga (tire de tests/Arch/divida.php, regra {$regra}):";
            foreach ($pagas as $chave) {
                $linhas[] = '- '.$chave;
            }
        }

        $this->fail(implode("\n", $linhas));
    }

    /**
     * @param  list<array{chave: string, texto: string, medida?: int}>  $violacoes
     */
    private static function gravarDivida(string $arquivo, string $regra, array $violacoes): void
    {
        $atual = is_file($arquivo) ? (json_decode((string) file_get_contents($arquivo), true) ?: []) : [];
        $atual[$regra] ??= [];
        foreach ($violacoes as $violacao) {
            $atual[$regra][$violacao['chave']] = ['texto' => $violacao['texto'], 'medida' => $violacao['medida'] ?? null];
        }
        file_put_contents($arquivo, json_encode($atual, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /** @return array<string, string|array{motivo: string, teto?: int}> chave => motivo */
    private static function dividaDa(string $regra): array
    {
        $arquivo = Codigo::raiz().'/tests/Arch/divida.php';
        $divida = is_file($arquivo) ? require $arquivo : [];

        return $divida[$regra] ?? [];
    }
}
