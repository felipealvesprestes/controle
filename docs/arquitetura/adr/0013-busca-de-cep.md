# ADR-0013 — Busca de endereço pelo CEP com a ViaCEP

| | |
|---|---|
| **Status** | Aceita |
| **Data** | 2026-10-29 |

## Contexto

Os formulários de endereço (dados da empresa no F-02, clientes no F-03) devem se preencher sozinhos a partir do CEP, como na maioria dos sistemas brasileiros. É a primeira vez que o sistema chama um serviço externo.

## Decisão

- A busca usa a **ViaCEP** (`https://viacep.com.br/ws/{cep}/json/`): gratuita, sem chave e sem cadastro.
- A chamada sai do **servidor**, pela porta `App\Domain\Address\AddressLookup` e pelo adaptador `App\Infrastructure\Address\ViaCepAddressLookup`, com o cliente HTTP do Laravel. Nenhuma dependência nova.
- O caso de uso `LookUpZipCode` checa a permissão de edição do formulário (SEC-02) e limita a 20 buscas por minuto por usuário (SEC-30).
- O adaptador só monta a URL com 8 dígitos, não segue redirecionamentos, usa timeout de 2 s para conectar e 3 s para responder e faz no máximo 2 tentativas (SEC-48).
- A resposta é tratada como dado externo: só textos, cortados no tamanho das colunas, UF conferida contra a lista oficial; sem cidade ou UF válida, conta como indisponível.
- Endereços encontrados ficam 30 dias no cache.
- A busca só preenche logradouro, bairro, cidade e UF, e só com valores não vazios. Número e complemento nunca são tocados. Se a busca falhar, aparece um aviso no campo e o preenchimento manual continua possível.

## Alternativas consideradas

- **Chamar a ViaCEP pelo navegador** — exigiria liberar o domínio na CSP (`connect-src`, SEC-09) e espalharia a regra entre o servidor e o JavaScript.
- **BrasilAPI ou Correios** — a BrasilAPI agrega várias fontes e serve de reserva, mas depende de terceiros também; a API dos Correios exige contrato. A porta `AddressLookup` permite trocar ou encadear um adaptador de reserva sem mudar telas nem casos de uso.

## Consequências

- O servidor de produção precisa de saída HTTPS para `viacep.com.br` (ver [operacao/deploy.md](../../operacao/deploy.md)).
- O CEP digitado é enviado à ViaCEP. Para a empresa é dado público; quando o F-03 usar a busca para clientes pessoa física, registrar isso na seção de privacidade do F-03.
- Os testes nunca chamam serviços externos: `Http::preventStrayRequests()` no `TestCase` faz qualquer chamada não simulada falhar.
- O código IBGE do município, que a ViaCEP também devolve, não é guardado agora; entra com a NFS-e (v1.1) se o Emissor Nacional exigir.
