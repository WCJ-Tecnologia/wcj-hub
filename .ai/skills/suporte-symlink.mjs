/**
 * Symlink de pasta nos testes das skills.
 *
 * O instalador e o portão do `.specs/` precisam recusar pasta que é link para fora do
 * projeto, e provar isso exige criar um symlink no teste. No Windows, criar symlink pede
 * Modo de Desenvolvedor ou administrador: sem isso o `symlinkSync` sai com `EPERM` e os
 * dois casos falhavam por ambiente, não por regra quebrada.
 *
 * No CI não se pula: lá o symlink funciona, e pular esconderia regra quebrada — mesmo
 * critério do `GITLEAKS_BIN` em `adapters/guard/guard.test.mjs`.
 */
import { mkdirSync, mkdtempSync, rmSync, symlinkSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';

export function motivoParaPularSymlink() {
    const base = mkdtempSync(join(tmpdir(), 'wcj-symlink-'));
    try {
        mkdirSync(join(base, 'alvo'));
        symlinkSync(join(base, 'alvo'), join(base, 'link'), 'dir');
        return false;
    } catch {
        return process.env.CI
            ? false
            : 'este sistema não deixa criar symlink de pasta (no Windows, ligue o Modo de Desenvolvedor); o CI roda este caso';
    } finally {
        rmSync(base, { recursive: true, force: true });
    }
}
