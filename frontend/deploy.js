const FtpDeploy = require("ftp-deploy");
const ftpDeploy = new FtpDeploy();

const config = {
  user: "inves783_control",
  password: "100%Control!!",
  host: "ftp.investsolucoesdigitais.com.br",
  port: 21,
  localRoot: __dirname + "/build",
  remoteRoot: "/public_html/controleflex/",
  include: ["*", "**/*"],
  deleteRemote: true,
  forcePasv: true,
};

ftpDeploy
  .deploy(config)
  .then(res => console.log("✅ Deploy finalizado com sucesso:", res))
  .catch(err => console.error("❌ Erro no deploy:", err));
