import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPen, faTrash } from '@fortawesome/free-solid-svg-icons';

const ListaFamiliares = ({ familiares, onEditar, onExcluir }) => {
  const placeholderSvg =
    'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjZGRkZGRkIi8+PHRleHQgeD0iMzAiIHk9IjMwIiBmb250LXNpemU9IjEwIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjNzc3Ij5TZW0gRm90bzwvdGV4dD48L3N2Zz4=';

  const formatCurrency = (value) => {
    if (value === null || value === undefined || isNaN(value)) return 'R$ 0,00';
    return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  };

  return (
    <div className="lista-familiares-wrapper">
      <div className="tabela-scroll">
        <table className="tabela-familiares">
          <thead>
            <tr>
              <th>Foto</th>
              <th>Nome</th>
              <th>Renda Total</th>
              <th>Limite Cartão</th>
              <th>Limite Cheque</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            {familiares.length === 0 ? (
              <tr>
                <td colSpan="6" style={{ textAlign: 'center' }}>
                  Nenhum familiar cadastrado
                </td>
              </tr>
            ) : (
              familiares.map((familiar) => {
                const fotoUrl = familiar.foto
                  ? `${process.env.REACT_APP_IMG_BASE_URL}/${familiar.foto}`
                  : placeholderSvg;

                return (
                  <tr key={familiar.id}>
                    <td>
                      <img
                        src={fotoUrl}
                        alt={familiar.nome}
                        style={{ width: 60, height: 60, objectFit: 'cover', borderRadius: '4px' }}
                        onError={(e) => (e.target.src = placeholderSvg)}
                      />
                    </td>
                    <td>{familiar.nome}</td>
                    <td>{formatCurrency(parseFloat(familiar.renda_total))}</td>
                    <td>{formatCurrency(parseFloat(familiar.limiteCartao))}</td>
                    <td>{formatCurrency(parseFloat(familiar.limiteCheque))}</td>
                    <td>
                      <div className="acao-botoes">
                        <button
                          onClick={() => onEditar(familiar.id)}
                          className="btn-icon btn-editar"
                          title="Editar"
                          aria-label={`Editar familiar ${familiar.nome}`}
                        >
                          <FontAwesomeIcon icon={faPen} />
                        </button>
                        <button
                          onClick={() => onExcluir(familiar.id)}
                          className="btn-icon btn-excluir"
                          title="Excluir"
                          aria-label={`Excluir familiar ${familiar.nome}`}
                        >
                          <FontAwesomeIcon icon={faTrash} />
                        </button>
                      </div>
                    </td>
                  </tr>
                );
              })
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default ListaFamiliares;
