@extends('legal.layout')

@section('title', 'Política de Privacidade')

@section('content')
  <h1>Política de <span>Privacidade</span></h1>
  <p class="meta">Última atualização: 15 de abril de 2026</p>

  <div class="callout">
    Esta política descreve como o AlfaHome coleta, utiliza, armazena e protege os
    dados pessoais dos seus usuários, em conformidade com a Lei Geral de Proteção
    de Dados (Lei nº 13.709/2018 — LGPD).
  </div>

  <h2>1. Quem somos</h2>
  <p>
    O AlfaHome é um serviço de gestão financeira pessoal e familiar operado pela
    <strong>Alfa Soluções Tecnológicas</strong> (CNPJ 52.638.029/0001-05), doravante denominada
    simplesmente &ldquo;AlfaHome&rdquo; ou &ldquo;nós&rdquo;. Somos o <strong>controlador</strong> dos dados pessoais
    tratados nesta plataforma, nos termos do art. 5º, VI, da LGPD.
  </p>
  <p>
    Para contatos relacionados a esta política, direitos do titular ou incidentes
    de segurança, escreva para <a href="mailto:alfa.comercial.solucoes@gmail.com">alfa.comercial.solucoes@gmail.com</a>.
  </p>

  <h2>2. Dados que coletamos</h2>
  <p>Coletamos apenas os dados necessários para prestar o serviço contratado:</p>

  <h3>Dados de cadastro</h3>
  <ul>
    <li>Nome completo</li>
    <li>Endereço de e-mail</li>
    <li>Senha (armazenada de forma criptografada, nunca em texto puro)</li>
    <li>Nome do grupo familiar (tenant)</li>
  </ul>

  <h3>Dados financeiros inseridos por você</h3>
  <ul>
    <li>Nomes e salários de familiares (quando você opta por cadastrá-los)</li>
    <li>Contas bancárias e cartões de crédito (apenas nome do banco, saldo, limite e datas de vencimento — <strong>nunca números de conta, agência, senha, CVV ou token de acesso</strong>)</li>
    <li>Despesas, receitas e investimentos que você registra manualmente</li>
    <li>Categorias, observações e descrições de lançamentos</li>
  </ul>

  <h3>Dados técnicos</h3>
  <ul>
    <li>Endereço IP, tipo de navegador e sistema operacional (em logs de acesso)</li>
    <li>Cookies estritamente essenciais (sessão autenticada e proteção CSRF)</li>
    <li>Data e hora das ações realizadas na conta (para auditoria e suporte)</li>
  </ul>

  <div class="callout">
    <strong>Importante:</strong> o AlfaHome <strong>não se conecta</strong> a instituições financeiras,
    não utiliza Open Finance, não importa extratos automaticamente e não pede
    credenciais bancárias. Todo lançamento é feito manualmente pelo próprio usuário.
  </div>

  <h2>3. Por que coletamos (finalidade)</h2>
  <ul>
    <li><strong>Executar o contrato</strong> com você: permitir cadastro, autenticação, uso das funcionalidades e suporte (base legal: art. 7º, V, da LGPD).</li>
    <li><strong>Cumprir obrigações legais e regulatórias</strong>, como emissão de recibos e retenção fiscal (art. 7º, II).</li>
    <li><strong>Legítimo interesse</strong> para manter a segurança do serviço, prevenir fraudes e melhorar a experiência (art. 7º, IX).</li>
    <li><strong>Comunicações transacionais</strong> essenciais (confirmação de conta, alertas de cobrança, avisos de manutenção). Não enviamos marketing sem seu consentimento prévio.</li>
  </ul>

  <h2>4. Com quem compartilhamos seus dados</h2>
  <p>
    Seus dados <strong>não são vendidos, alugados ou cedidos</strong> a terceiros para fins
    publicitários. Eles são acessados apenas por:
  </p>
  <ul>
    <li><strong>Subprocessadores operacionais</strong> estritamente necessários: provedor de hospedagem em nuvem (onde o banco de dados roda), provedor de envio de e-mails transacionais, serviço de backup criptografado.</li>
    <li><strong>Autoridades públicas</strong>, quando exigido por lei, ordem judicial ou requisição formal.</li>
    <li><strong>Dentro do seu próprio grupo familiar (tenant)</strong>: se você convida outros usuários para a mesma conta (planos Casal ou Família), eles enxergam os dados financeiros compartilhados. O controle de quem acessa é seu.</li>
  </ul>
  <p>
    Nenhum dado é compartilhado com redes de anúncios, data brokers, bureaus de
    crédito ou empresas de marketing.
  </p>

  <h2>5. Onde os dados ficam armazenados</h2>
  <p>
    Os dados são armazenados em servidores localizados no Brasil. Backups
    criptografados são mantidos em serviço de armazenamento em nuvem com
    retenção controlada. A senha é armazenada com hash <em>bcrypt</em>; a comunicação
    entre o seu navegador e nossos servidores é sempre protegida por HTTPS/TLS.
  </p>

  <h2>6. Por quanto tempo guardamos</h2>
  <ul>
    <li><strong>Enquanto a conta estiver ativa:</strong> mantemos todos os dados inseridos.</li>
    <li><strong>Após cancelamento:</strong> os dados permanecem por até 30 dias para eventual reativação. Após esse prazo, são excluídos permanentemente, exceto quando a legislação exigir guarda por período maior (ex: dados fiscais por 5 anos).</li>
    <li><strong>Logs de acesso e auditoria:</strong> retidos por 6 meses para fins de segurança, nos termos do Marco Civil da Internet.</li>
  </ul>

  <h2>7. Seus direitos como titular</h2>
  <p>
    A LGPD garante a você, a qualquer momento e sem custo, os seguintes direitos
    (art. 18):
  </p>
  <ul>
    <li>Confirmar a existência de tratamento dos seus dados</li>
    <li>Acessar os dados que temos sobre você</li>
    <li>Corrigir dados incompletos, inexatos ou desatualizados</li>
    <li>Solicitar anonimização, bloqueio ou eliminação de dados desnecessários</li>
    <li>Solicitar portabilidade dos dados</li>
    <li>Eliminar dados tratados com base no consentimento</li>
    <li>Obter informação sobre com quem compartilhamos seus dados</li>
    <li>Revogar o consentimento, quando aplicável</li>
  </ul>
  <p>
    Para exercer qualquer desses direitos, envie um e-mail para
    <a href="mailto:alfa.comercial.solucoes@gmail.com">alfa.comercial.solucoes@gmail.com</a>.
    Respondemos em até 15 dias.
  </p>

  <h2>8. Cookies</h2>
  <p>
    Utilizamos <strong>apenas cookies estritamente necessários</strong> para manter você
    autenticado na sessão e proteger contra ataques CSRF. Não usamos cookies de
    rastreamento, análise de comportamento nem publicidade. Você pode bloqueá-los
    no navegador, mas nesse caso o login deixará de funcionar.
  </p>

  <h2>9. Segurança</h2>
  <p>
    Adotamos medidas técnicas e administrativas razoáveis para proteger seus
    dados contra acessos não autorizados, perda, alteração ou destruição:
    criptografia em trânsito (HTTPS/TLS), senhas com hash, controle de acesso
    multi-tenant isolado por cliente, backups diários criptografados e monitoramento
    contínuo.
  </p>
  <p>
    Em caso de incidente de segurança que possa causar risco relevante aos
    titulares, comunicaremos os afetados e a ANPD no prazo legal.
  </p>

  <h2>10. Crianças e adolescentes</h2>
  <p>
    O AlfaHome não é destinado a menores de 18 anos. Não coletamos dados de
    crianças e adolescentes conscientemente. Se um responsável legal cadastra
    dependentes no grupo familiar, o tratamento ocorre sob responsabilidade e
    consentimento desse titular adulto.
  </p>

  <h2>11. Alterações nesta política</h2>
  <p>
    Podemos atualizar esta política para refletir mudanças legais, técnicas ou
    operacionais. Quando houver alteração relevante, avisaremos por e-mail e/ou
    aviso dentro do sistema com pelo menos 15 dias de antecedência.
  </p>

  <h2>12. Contato</h2>
  <p>
    Dúvidas, solicitações ou reclamações sobre privacidade:<br>
    <strong>E-mail:</strong> <a href="mailto:alfa.comercial.solucoes@gmail.com">alfa.comercial.solucoes@gmail.com</a>
  </p>
  <p>
    Você também pode registrar reclamação diretamente na Autoridade Nacional de
    Proteção de Dados (ANPD) através de <a href="https://www.gov.br/anpd" target="_blank" rel="noopener">gov.br/anpd</a>.
  </p>
@endsection
