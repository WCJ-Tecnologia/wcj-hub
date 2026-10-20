# WQualityTabOrganism

Organismo · `organisms/w-quality-tab-organism/w-quality-tab-organism.tsx` · story `Organisms/Indicadores/Qualidade`

## O que faz

Aba _Qualidade_ (F7 §03, §06; P25): o total de bugs de produção no topo e a lista, do mais recente para o mais antigo: Bug (chave e título), Tarefa original (chave e título), Criado em, Etapa atual. O arquivado fica, com a marca. Não é retrabalho de ninguém. Vazia: "Nenhum bug de produção registrado neste projeto".

## Props

| Prop   | Tipo         | Obrigatória | Para quê                                           |
| ------ | ------------ | ----------- | -------------------------------------------------- |
| `data` | `QualityTab` | sim         | total e linhas, como o `SituationIndicators` manda |
