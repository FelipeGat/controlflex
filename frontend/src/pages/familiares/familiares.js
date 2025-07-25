// familiares.js
import React, { useState, useEffect } from 'react';
import ListaFamiliares from './listafamiliares';
import FormularioFamiliar from './formulariofamiliar';
import axios from 'axios';
import './familiares.css';
import { API_BASE_URL } from '../../apiConfig';

// FontAwesome
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPlus, faSpinner } from '@fortawesome/free-solid-svg-icons';

export default function Familiares() {
  const [familiares, setFamiliares] = useState([]);
  const [formAberto, setFormAberto] = useState(false);
  const [familiarEditando, setFamiliarEditando] = useState(null);
  const [carregando, setCarregando] = useState(false);

  const usuarioId = 1; // Simulando usuário logado

  // Buscar familiares
  const buscarFamiliares = async () => {
    setCarregando(true);
    try {
      const res = await axios.get(`${API_BASE_URL}/familiares/listar.php`);
      setFamiliares(res.data);
    } catch (err) {
      alert('Erro ao buscar familiares');
      console.error(err);
    } finally {
      setCarregando(false);
    }
  };

  useEffect(() => {
    buscarFamiliares();
  }, []);

  const abrirFormNovo = () => {
    setFamiliarEditando(null);
    setFormAberto(true);
  };

  const abrirFormEditar = (familiarId) => {
    const fam = familiares.find((f) => f.id === familiarId);
    if (fam) {
      setFamiliarEditando(fam);
      setFormAberto(true);
    }
  };

  const fecharForm = () => {
    setFormAberto(false);
    setFamiliarEditando(null);
  };

  const aoSalvar = () => {
    buscarFamiliares();
    fecharForm();
  };

  const excluirFamiliar = async (id) => {
    if (!window.confirm('Deseja realmente excluir este familiar?')) return;
    try {
      await axios.post(`${API_BASE_URL}/familiares/excluir.php`, { id });
      setFamiliares((prev) => prev.filter((f) => f.id !== id)); // otimização local
    } catch (err) {
      alert('Erro ao excluir familiar');
      console.error(err);
    }
  };

  return (
    <div className="page-container">
      {/* Formulário de cadastro/edição */}
      {formAberto && (
        <FormularioFamiliar
          familiarId={familiarEditando ? familiarEditando.id : null}
          usuarioId={usuarioId}
          onCancelar={fecharForm}
          onSucesso={aoSalvar}
        />
      )}

      {/* Container principal com título e tabela */}
      <div className="familiares-table-container">
        <div className="familiares-header" style={{ marginBottom: '15px' }}>
          <h1 className="form-title">Lista de Familiares</h1>
          <button className="btn-salvar" onClick={abrirFormNovo}>
            <FontAwesomeIcon icon={faPlus} style={{ marginRight: '6px' }} />
            Novo Familiar
          </button>
        </div>

        {carregando ? (
          <div className="loading">
            <FontAwesomeIcon icon={faSpinner} spin /> Carregando...
          </div>
        ) : (
          <ListaFamiliares
            familiares={familiares}
            onEditar={abrirFormEditar}
            onExcluir={excluirFamiliar}
          />
        )}
      </div>
    </div>
  );
}
