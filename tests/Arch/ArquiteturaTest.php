<?php

namespace Tests\Arch;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Tests\Arch\Support\Codigo;
use Tests\Arch\Support\VerificaArquitetura;

/**
 * Regras de forma do código que viraram teste (skill wcj-testes, references/arquitetura.md).
 * Violação que já existia fica em tests/Arch/divida.php; violação nova falha.
 * Não sobe o Laravel: lê o código.
 */
class ArquiteturaTest extends TestCase
{
    use VerificaArquitetura;

    // Limites de .ai/stacks/laravel-inertia.md §5 e .ai/core/principios.md.
    private const LINHAS_CONTROLLER = 200;

    private const LINHAS_METODO_CONTROLLER = 30;

    private const LINHAS_SERVICE = 300;

    private const LINHAS_ARQUIVO = 400;

    public function test_a1_sem_depuracao_esquecida(): void
    {
        $this->regraDeUso('A1', 'Sem depuração esquecida', 'app',
            ['dd()', 'ddd()', 'dump()', 'var_dump()', 'ray()', 'phpinfo()', 'exit()', 'debug_print_backtrace()',
                '->dd()', '->dump()', '->ddRawSql()', '->dumpRawSql()'],
            'apague a chamada. Para investigar em produção, use Log.');
    }

    public function test_a2_sem_funcao_insegura(): void
    {
        $this->regraDeUso('A2', 'Sem função insegura', 'app',
            // shell_exec() cobre também a crase (`comando`).
            ['eval()', 'exec()', 'shell_exec()', 'system()', 'passthru()', 'proc_open()', 'popen()',
                'unserialize()', 'extract()', 'md5()', 'sha1()', 'rand()', 'mt_rand()', 'uniqid()', 'str_shuffle()'],
            'comando externo via Process com argumentos em lista; json_decode no lugar de unserialize; '
            .'Hash::make para senha e hash("sha256") para o resto; random_int e Str::random para sorteio e token.');
    }

    public function test_a3_env_so_em_config(): void
    {
        $this->regraDeUso('A3', 'env() só em config', 'app', ['env()'],
            'leia config("arquivo.chave") e ponha o env() em config/. Com config:cache, env() fora de config/ devolve null.');
    }

    public function test_a4_service_nao_conhece_http(): void
    {
        // Por domínio (ADR-001): os Services moram em app/Platform/Services e app/Domains/*/Services.
        $this->regraDeUsoEm('A4', 'Service não conhece HTTP', self::pastasDe('Services'),
            // UploadedFile e File ficam de fora: são o arquivo, não a requisição.
            ['Illuminate\\Http\\Request', 'Illuminate\\Http\\Response', 'Illuminate\\Http\\JsonResponse',
                'Illuminate\\Http\\RedirectResponse', 'Illuminate\\Http\\Resources\\', 'Illuminate\\Http\\Exceptions\\',
                'Illuminate\\Foundation\\Http\\FormRequest', 'App\\Http\\Requests\\', 'Illuminate\\Routing\\',
                'Illuminate\\Support\\Facades\\Request', 'Illuminate\\Support\\Facades\\Response', 'Illuminate\\Support\\Facades\\Redirect',
                'Request', 'Response', 'Redirect', 'Inertia\\',
                'Symfony\\Component\\HttpFoundation\\Request', 'Symfony\\Component\\HttpFoundation\\Response',
                'Symfony\\Component\\HttpKernel\\Exception\\',
                'request()', 'response()', 'redirect()', 'back()', 'abort()', 'abort_if()', 'abort_unless()'],
            'o controller lê a requisição e passa o dado pronto; o Service lança exceção de domínio e o controller traduz para HTTP.');
    }

    public function test_a6_tamanho(): void
    {
        $violacoes = [];
        foreach (Codigo::arquivos('app') as $arquivo) {
            $limite = match (true) {
                self::camada($arquivo) === 'Controllers' => self::LINHAS_CONTROLLER,
                self::camada($arquivo) === 'Services' => self::LINHAS_SERVICE,
                default => self::LINHAS_ARQUIVO,
            };
            $linhas = Codigo::linhas($arquivo);
            if ($linhas > $limite) {
                $violacoes[] = ['chave' => $arquivo, 'texto' => "{$arquivo} tem {$linhas} linhas (limite {$limite})", 'medida' => $linhas];
            }
        }
        $metodos = array_merge(...array_map(fn (string $pasta): array => Codigo::metodos($pasta), self::pastasDe('Controllers')));
        foreach ($metodos as $metodo) {
            if ($metodo['linhas'] > self::LINHAS_METODO_CONTROLLER) {
                $violacoes[] = [
                    'chave' => "{$metodo['arquivo']}::{$metodo['nome']}",
                    'texto' => "{$metodo['arquivo']}:{$metodo['linha']} {$metodo['nome']}() tem {$metodo['linhas']} linhas (limite ".self::LINHAS_METODO_CONTROLLER.')',
                    'medida' => $metodo['linhas'],
                ];
            }
        }

        $this->semViolacoes('A6', 'Tamanho', $violacoes,
            'controller grande: a regra vai para Service, FormRequest ou query dedicada; Service grande: divida por caso de uso.');
    }

    public function test_a7_dominio_nao_usa_model_de_outro(): void
    {
        $dominios = array_map('basename', glob(Codigo::raiz().'/app/Domains/*', GLOB_ONLYDIR) ?: []);
        if ($dominios === []) {
            $this->markTestSkipped('Sem app/Domains: a regra vale quando o projeto é organizado por domínio.');
        }

        $usos = [];
        foreach ($dominios as $dominio) {
            $outros = array_map(fn (string $outro): string => "App\\Domains\\{$outro}\\Models\\", array_values(array_diff($dominios, [$dominio])));
            $usos = array_merge($usos, Codigo::violacoes("app/Domains/{$dominio}", $outros));
        }
        $violacoes = self::violacoesPorArquivo($usos);

        $this->semViolacoes('A7', 'Domínio não usa Model de outro domínio', $violacoes,
            'chame o Service do outro domínio; a tabela dele só ele lê e grava.');
    }

    /**
     * A9 (ADR-001): domínio não lê nem grava tabela de outro. A7 já barra o Model de outro
     * domínio; aqui vão (a) o Platform usando qualquer coisa de App\Domains (o Núcleo não
     * conhece módulo: as FKs vão só de Domains para Platform, banco.md 6.2) e (b) o nome de uma
     * tabela de outro domínio escrito à mão (DB::table('tasks'), join, SQL cru). Um domínio
     * pode ler as tabelas do Platform, na direção das FKs; o Platform não lê as dos domínios.
     * O dono de cada tabela sai dos Models de app/Platform/Models e app/Domains/<X>/Models.
     */
    public function test_a9_dominio_nao_le_tabela_de_outro(): void
    {
        $donos = self::donosDasTabelas();
        if ($donos === []) {
            $this->markTestSkipped('Sem Models em app/Platform/Models nem app/Domains/*/Models.');
        }

        $usos = Codigo::violacoes('app/Platform', ['App\\Domains\\']);

        $areas = array_values(array_unique($donos));
        foreach ($areas as $area) {
            $pasta = $area === 'Platform' ? 'app/Platform' : "app/Domains/{$area}";
            foreach (Codigo::textos($pasta) as $texto) {
                $tabela = strtolower(strtok(trim($texto['valor']), '. '));
                $dono = $donos[$tabela] ?? null;
                // Do próprio domínio, ou do Platform lido por um domínio: pode.
                if ($dono === null || $dono === $area || $dono === 'Platform') {
                    continue;
                }
                if ($texto['valor'] !== $tabela && ! str_starts_with($texto['valor'], $tabela.'.')) {
                    continue; // texto comum que só começa com a palavra
                }
                $usos[] = ['arquivo' => $texto['arquivo'], 'linha' => $texto['linha'], 'nome' => "tabela {$tabela} ({$dono})"];
            }
        }

        $this->semViolacoes('A9', 'Domínio não lê tabela de outro', self::violacoesPorArquivo($usos),
            'chame o Service do domínio dono da tabela. O Platform (Núcleo) não conhece módulo: quem precisa do dado do módulo é o módulo.');
    }

    /**
     * Tabela => domínio dono ('Platform' ou o nome em app/Domains), lido de cada Model.
     *
     * @return array<string, string>
     */
    private static function donosDasTabelas(): array
    {
        $pastas = ['Platform' => 'app/Platform/Models'];
        foreach (glob(Codigo::raiz().'/app/Domains/*', GLOB_ONLYDIR) ?: [] as $dominio) {
            $pastas[basename($dominio)] = 'app/Domains/'.basename($dominio).'/Models';
        }

        $donos = [];
        foreach ($pastas as $area => $pasta) {
            foreach (glob(Codigo::raiz()."/{$pasta}/*.php") ?: [] as $arquivo) {
                $classe = 'App\\'.str_replace('/', '\\', substr($pasta, 4)).'\\'.basename($arquivo, '.php');
                if (class_exists($classe) && is_subclass_of($classe, Model::class) && ! (new \ReflectionClass($classe))->isAbstract()) {
                    $donos[(new $classe)->getTable()] = $area;
                }
            }
        }

        return $donos;
    }

    /**
     * Pastas de uma camada nos domínios (ADR-001): app/Http/<camada> (infraestrutura),
     * app/Platform/<camada> e app/Domains/*\/<camada>.
     *
     * @return list<string>
     */
    private static function pastasDe(string $camada): array
    {
        $pastas = ["app/Http/{$camada}", "app/Platform/{$camada}"];
        foreach (glob(Codigo::raiz().'/app/Domains/*', GLOB_ONLYDIR) ?: [] as $dominio) {
            $pastas[] = 'app/Domains/'.basename($dominio)."/{$camada}";
        }

        return $pastas;
    }

    /** A camada do arquivo (Controllers, Services…) em app/Http, app/Platform ou app/Domains/<X>. */
    private static function camada(string $arquivo): ?string
    {
        return preg_match('#^app/(?:Http|Platform|Domains/[^/]+)/([^/]+)/#', $arquivo, $m) === 1 ? $m[1] : null;
    }

    /**
     * @param  list<string>  $pastas
     * @param  list<string>  $alvos
     */
    private function regraDeUsoEm(string $regra, string $titulo, array $pastas, array $alvos, string $comoCorrigir): void
    {
        $usos = array_merge(...array_map(fn (string $pasta): array => Codigo::violacoes($pasta, $alvos), $pastas));
        $this->semViolacoes($regra, $titulo, self::violacoesPorArquivo($usos), $comoCorrigir);
    }

    /**
     * @param  list<string>  $alvos
     */
    private function regraDeUso(string $regra, string $titulo, string $pasta, array $alvos, string $comoCorrigir): void
    {
        $this->semViolacoes($regra, $titulo, self::violacoesPorArquivo(Codigo::violacoes($pasta, $alvos)), $comoCorrigir);
    }

    /**
     * Uma violação por "arquivo usa nome", com a quantidade de usos como medida: na dívida,
     * a quantidade vira o teto, e um uso a mais no mesmo arquivo falha.
     *
     * @param  list<array{arquivo: string, linha: int, nome: string}>  $usos
     * @return list<array{chave: string, texto: string, medida: int}>
     */
    private static function violacoesPorArquivo(array $usos): array
    {
        $grupos = [];
        foreach ($usos as $uso) {
            $grupos["{$uso['arquivo']} usa {$uso['nome']}"][] = $uso;
        }
        $violacoes = [];
        foreach ($grupos as $chave => $lista) {
            $linhas = implode(', ', array_column($lista, 'linha'));
            $violacoes[] = [
                'chave' => $chave,
                'texto' => "{$lista[0]['arquivo']}:{$lista[0]['linha']} usa {$lista[0]['nome']}".(count($lista) > 1 ? ' ('.count($lista)." vezes: linhas {$linhas})" : ''),
                'medida' => count($lista),
            ];
        }

        return $violacoes;
    }
}
