import React, { useState, useEffect } from 'react';
import apiConfig from '../../apiConfig';

const FormularioFamiliar = ({ familiarId, usuarioId, onSucesso, onCancelar }) => {
  const [nome, setNome] = useState('');
  const [rendaTotal, setRendaTotal] = useState('');
  const [limiteCartao, setLimiteCartao] = useState('');
  const [limiteCheque, setLimiteCheque] = useState('');
  const [fotoFile, setFotoFile] = useState(null);
  const [fotoAtual, setFotoAtual] = useState(null); // <- NOVO: guarda nome da imagem atual

  const [carregando, setCarregando] = useState(false);
  const [erro, setErro] = useState('');

  useEffect(() => {
    if (familiarId) {
      setCarregando(true);
      setErro('');
      fetch(`${apiConfig.API_BASE_URL}/familiares/listar.php?id=${familiarId}`)
        .then(res => res.json())
        .then(data => {
          if (data.erro) {
            setErro(data.erro);
            // Limpa os campos se erro
            setNome('');
            setRendaTotal('');
            setLimiteCartao('');
            setLimiteCheque('');
            setFotoFile(null);
            setFotoAtual(null);
          } else {
            setNome(data.nome || '');
            setRendaTotal(data.renda_total || '');
            setLimiteCartao(data.limiteCartao || '');
            setLimiteCheque(data.limiteCheque || '');
            setFotoAtual(data.foto || null); // <- salva a imagem atual
            setFotoFile(null); // limpa input de upload
          }
        })
        .catch(() => {
          setErro('Erro ao carregar os dados do familiar.');
          setNome('');
          setRendaTotal('');
          setLimiteCartao('');
          setLimiteCheque('');
          setFotoFile(null);
          setFotoAtual(null);
        })
        .finally(() => setCarregando(false));
    } else {
      // Novo cadastro
      setNome('');
      setRendaTotal('');
      setLimiteCartao('');
      setLimiteCheque('');
      setFotoFile(null);
      setFotoAtual(null);
      setErro('');
    }
  }, [familiarId]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErro('');
    setCarregando(true);

    try {
      const formData = new FormData();
      formData.append('nome', nome);
      formData.append('renda_total', rendaTotal);
      formData.append('limiteCartao', limiteCartao);
      formData.append('limiteCheque', limiteCheque);

      if (!familiarId) {
        formData.append('usuario_id', usuarioId);
      } else {
        formData.append('id', familiarId);
      }

      if (fotoFile) {
        formData.append('foto', fotoFile);
      }

      const response = await fetch(`${apiConfig.API_BASE_URL}/familiares/salvar.php`, {
        method: 'POST',
        body: formData,
      });

      const resultado = await response.json();

      if (resultado.sucesso) {
        // Se não foi enviada nova imagem, mantém a anterior
        if (!fotoFile) {
          resultado.foto = fotoAtual;
        }
        onSucesso && onSucesso(resultado);
      } else {
        setErro(resultado.erro || 'Erro desconhecido ao salvar.');
      }
    } catch {
      setErro('Erro ao salvar o familiar. Tente novamente.');
    } finally {
      setCarregando(false);
    }
  };

  return (
    <div className="form-card">
      <h2 className="form-title">{familiarId ? 'Editar Familiar' : 'Novo Familiar'}</h2>

      {erro && <div style={{ color: 'red', marginBottom: 10 }}>{erro}</div>}

      <form onSubmit={handleSubmit} encType="multipart/form-data">
        <input
          className="form-control"
          type="text"
          placeholder="Nome"
          value={nome}
          onChange={e => setNome(e.target.value)}
          required
          disabled={carregando}
        />

        <input
          className="form-control"
          type="number"
          placeholder="Renda Total"
          value={rendaTotal}
          onChange={e => setRendaTotal(e.target.value)}
          min="0"
          step="0.01"
          required
          disabled={carregando}
        />

        <input
          className="form-control"
          type="number"
          placeholder="Limite Cartão Total"
          value={limiteCartao}
          onChange={e => setLimiteCartao(e.target.value)}
          min="0"
          step="0.01"
          required
          disabled={carregando}
        />

        <input
          className="form-control"
          type="number"
          placeholder="Limite Cheque Total"
          value={limiteCheque}
          onChange={e => setLimiteCheque(e.target.value)}
          min="0"
          step="0.01"
          required
          disabled={carregando}
        />

        <label>
          Foto:
          <input
            type="file"
            accept="image/*"
            onChange={e => setFotoFile(e.target.files[0])}
            disabled={carregando}
          />
        </label>

        {/* Mostrar imagem atual, se estiver em edição e não for enviada nova imagem */}
        {fotoAtual && !fotoFile && (
          <div style={{ marginTop: 10 }}>
            <strong>Imagem atual:</strong>
            <br />
            <img
              src={`${process.env.REACT_APP_IMG_BASE_URL}/${fotoAtual}`}
              alt="Foto atual"
              style={{ width: 100, height: 100, objectFit: 'cover', marginTop: 5 }}
            />
          </div>
        )}

        <div className="botoes-cadastro">
          <button className="btn-salvar" type="submit" disabled={carregando}>
            {carregando ? 'Salvando...' : 'Salvar'}
          </button>
          <button
            className="btn-cancelar"
            type="button"
            onClick={() => onCancelar && onCancelar()}
            disabled={carregando}
          >
            Cancelar
          </button>
        </div>
      </form>
    </div>
  );
};

export default FormularioFamiliar;
