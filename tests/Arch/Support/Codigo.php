<?php

namespace Tests\Arch\Support;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Motor dos testes de arquitetura (skill wcj-testes): lê o código com o php-parser,
 * que já vem com o PHPUnit, e devolve o que cada arquivo usa, com a linha.
 */
final class Codigo
{
    /** Métodos que também contam como uso: depuração esquecida em query builder e collection. */
    public const METODOS_VIGIADOS = ['dd', 'dump', 'ddRawSql', 'dumpRawSql'];

    /** @var array<string, array<int, Node>> */
    private static array $arvores = [];

    /**
     * Arquivos .php sob a pasta, relativos à raiz do projeto, em ordem.
     *
     * @return list<string>
     */
    public static function arquivos(string $pasta): array
    {
        $raiz = self::raiz();
        $base = $raiz.'/'.trim($pasta, '/');
        if (! is_dir($base)) {
            return [];
        }

        $arquivos = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS)) as $arquivo) {
            if ($arquivo->getExtension() === 'php') {
                $arquivos[] = str_replace('\\', '/', substr($arquivo->getPathname(), strlen($raiz) + 1));
            }
        }
        sort($arquivos);

        return $arquivos;
    }

    /**
     * Cada uso de classe (nome completo, sem a barra inicial) ou de função ("nome()").
     * eval, exit/die e a crase (`cmd`, que é shell_exec) também saem como função; método
     * vigiado sai como "->nome()". Nomes de namespace e de constante (true, null) não contam.
     *
     * @return list<array{arquivo: string, linha: int, nome: string}>
     */
    public static function usos(string $pasta): array
    {
        $usos = [];
        foreach (self::arquivos($pasta) as $arquivo) {
            $coletor = new class extends NodeVisitorAbstract
            {
                /** @var list<array{linha: int, nome: string}> */
                public array $usos = [];

                public function enterNode(Node $no): ?int
                {
                    $nome = match (true) {
                        $no instanceof Node\Expr\FuncCall && $no->name instanceof Node\Name => $no->name->toString().'()',
                        $no instanceof Node\Expr\Eval_ => 'eval()',
                        $no instanceof Node\Expr\Exit_ => 'exit()',
                        $no instanceof Node\Expr\ShellExec => 'shell_exec()',
                        ($no instanceof Node\Expr\MethodCall || $no instanceof Node\Expr\NullsafeMethodCall || $no instanceof Node\Expr\StaticCall)
                            && $no->name instanceof Node\Identifier
                            && in_array($no->name->toString(), Codigo::METODOS_VIGIADOS, true) => '->'.$no->name->toString().'()',
                        $no instanceof Node\Name && ! self::naoEhClasse($no) => $no->toString(),
                        default => null,
                    };
                    if ($nome !== null) {
                        $this->usos[] = ['linha' => $no->getStartLine(), 'nome' => $nome];
                    }

                    return null;
                }

                private static function naoEhClasse(Node\Name $no): bool
                {
                    $pai = $no->getAttribute('parent');

                    return $pai instanceof Node\Expr\FuncCall
                        || $pai instanceof Node\Expr\ConstFetch
                        || $pai instanceof Node\Stmt\Namespace_;
                }
            };
            (new NodeTraverser($coletor))->traverse(self::arvore($arquivo));
            foreach ($coletor->usos as $uso) {
                $usos[] = ['arquivo' => $arquivo, 'linha' => $uso['linha'], 'nome' => $uso['nome']];
            }
        }

        return $usos;
    }

    /**
     * Usos proibidos. Alvo terminado em "\" é prefixo de namespace; terminado em "()"
     * é função; qualquer outro é o nome exato da classe.
     *
     * @param  list<string>  $alvos
     * @return list<array{arquivo: string, linha: int, nome: string}>
     */
    public static function violacoes(string $pasta, array $alvos): array
    {
        return array_values(array_filter(self::usos($pasta), function (array $uso) use ($alvos): bool {
            foreach ($alvos as $alvo) {
                $casou = str_ends_with($alvo, '\\')
                    ? str_starts_with($uso['nome'], $alvo)
                    : strcasecmp($uso['nome'], $alvo) === 0;
                if ($casou) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Métodos declarados sob a pasta, com o tamanho em linhas (da assinatura ao fecha-chave).
     *
     * @return list<array{arquivo: string, linha: int, nome: string, linhas: int}>
     */
    public static function metodos(string $pasta): array
    {
        $metodos = [];
        foreach (self::arquivos($pasta) as $arquivo) {
            foreach ((new NodeFinder)->findInstanceOf(self::arvore($arquivo), Node\Stmt\ClassMethod::class) as $metodo) {
                $metodos[] = [
                    'arquivo' => $arquivo,
                    'linha' => $metodo->getStartLine(),
                    'nome' => $metodo->name->toString(),
                    'linhas' => $metodo->getEndLine() - $metodo->getStartLine() + 1,
                ];
            }
        }

        return $metodos;
    }

    public static function linhas(string $arquivo): int
    {
        return count(file(self::raiz().'/'.$arquivo) ?: []);
    }

    public static function raiz(): string
    {
        return str_replace('\\', '/', dirname(__DIR__, 3));
    }

    /** @return array<int, Node> */
    private static function arvore(string $arquivo): array
    {
        if (! isset(self::$arvores[$arquivo])) {
            $codigo = (string) file_get_contents(self::raiz().'/'.$arquivo);
            $arvore = (new ParserFactory)->createForHostVersion()->parse($codigo) ?? [];
            self::$arvores[$arquivo] = (new NodeTraverser(new NameResolver, new ParentConnectingVisitor))->traverse($arvore);
        }

        return self::$arvores[$arquivo];
    }
}
