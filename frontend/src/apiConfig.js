const isProduction = process.env.NODE_ENV === 'production';

// EXPORTA a variável para a API
export const API_BASE_URL = isProduction
  ? 'https://investsolucoesdigitais.com.br/controleflex/backend/api'
  : 'http://localhost/ControleFlex/backend/api';

// EXPORTA a variável para a pasta de uploads
export const UPLOADS_BASE_URL = isProduction
  ? 'https://investsolucoesdigitais.com.br/controleflex/backend/uploads'
  : 'http://localhost/ControleFlex/backend/uploads';

  // EXPORTA a variável para os ícones dos bancos
export const BANK_ICONS_BASE_URL = isProduction
  ? 'https://investsolucoesdigitais.com.br/controleflex/assets/img'
  : 'http://localhost/ControleFlex/assets/img';

// EXPORTA a variável para a URL pública do site
export const PUBLIC_URL = isProduction
  ? 'https://investsolucoesdigitais.com.br/controleflex'
  : ''; // Em desenvolvimento, a raiz é o próprio localhost

// O export default não é mais estritamente necessário, mas podemos manter por consistência
const apiConfig = {
    API_BASE_URL,
    UPLOADS_BASE_URL,
    BANK_ICONS_BASE_URL,
    PUBLIC_URL
};

export default apiConfig;
