import { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';

// Componente do novo modal de confirmação
const ConfirmacaoModal = ({ isOpen, onClose, onConfirm, item, contas }) => {
    const [data, setData] = useState('');
    const [contaSelecionada, setContaSelecionada] = useState('');

    // Sincroniza o estado inicial do modal com os dados do item
    useEffect(() => {
        if (item && isOpen) {
            // Define a data atual como padrão, formatada para YYYY-MM-DD
            const today = new Date().toISOString().split('T')[0];
            setData(today);

            // Limpa a conta selecionada ao abrir para evitar dados antigos
            setContaSelecionada('');
        }
    }, [isOpen, item]);

    if (!isOpen) {
        return null;
    }

    const handleConfirmClick = () => {
        if (data && contaSelecionada) {
            onConfirm(item.id, data, contaSelecionada);
        } else {
            // Em um aplicativo real, você exibirá uma mensagem de erro ou um tooltip
            console.error('Por favor, preencha a data e selecione uma conta.');
            alert('Por favor, preencha a data e selecione uma conta.');
        }
    };

    const tipo = item.tipo === 'receita' ? 'Recebimento' : 'Pagamento';
    const tipoCor = item.tipo === 'receita' ? 'success' : 'danger';

    // Usamos createPortal para garantir que o modal seja renderizado no topo da página
    return createPortal(
        <div className="modal-overlay">
            <div className="modal-content">
                <h3 className="modal-title">Confirmar {tipo}</h3>
                <p>Selecione a data e a conta para o lançamento:</p>
                <p className="font-semibold text-lg text-gray-800">"{item.descricao}"</p>

                <div className="mt-6 flex flex-col gap-4">
                    <div className="filtro-grupo">
                        <label htmlFor="data">{`Data de ${tipo}`}</label>
                        <input
                            type="date"
                            id="data"
                            className="form-control"
                            value={data}
                            onChange={(e) => setData(e.target.value)}
                        />
                    </div>

                    <div className="filtro-grupo">
                        <label htmlFor="conta">{`Conta de ${tipo === 'Pagamento' ? 'Origem' : 'Destino'}`}</label>
                        <select
                            id="conta"
                            className="form-control"
                            value={contaSelecionada}
                            onChange={(e) => setContaSelecionada(e.target.value)}
                        >
                            <option value="" disabled>Selecione uma conta...</option>
                            <optgroup label="Contas Bancárias">
                                {contas.map(conta => (
                                    <option key={conta.id} value={conta.id}>
                                        {`${conta.nome} (${conta.tipo}) - Saldo: R$ ${conta.saldo.toFixed(2).replace('.', ',')}`}
                                    </option>
                                ))}
                            </optgroup>
                        </select>
                    </div>
                </div>

                <div className="modal-buttons mt-8">
                    <button className="btn btn-secondary" onClick={onClose}>
                        Cancelar
                    </button>
                    <button className={`btn btn-${tipoCor}`} onClick={handleConfirmClick}>
                        Confirmar
                    </button>
                </div>
            </div>
        </div>,
        document.body // Renderiza o modal diretamente no body
    );
};

// Componente principal para demonstração do modal
const App = () => {
    const [modalOpen, setModalOpen] = useState(false);
    const [confirmado, setConfirmado] = useState(false);

    // Exemplo de item de lançamento (poderia vir de uma tabela)
    const itemExemplo = {
        id: '12345',
        descricao: 'Compra de material de escritório',
        tipo: 'despesa', // ou 'receita'
        valor: 150.75,
        status: 'pendente',
    };

    // Exemplo de lista de contas (poderia vir do banco de dados)
    const contasExemplo = [
        { id: 'c1', nome: 'Banco do Brasil', tipo: 'Débito', saldo: 9200.00 },
        { id: 'c2', nome: 'Sicoob', tipo: 'Débito', saldo: 311200.00 },
        { id: 'c3', nome: 'Conta Corrente', tipo: 'Corrente', saldo: 500.00 }
    ];

    const handleConfirm = (id, data, conta) => {
        // Lógica para processar o pagamento/recebimento
        console.log(`Lançamento ${id} confirmado!`);
        console.log(`Data: ${data}`);
        console.log(`Conta: ${contasExemplo.find(c => c.id === conta).nome}`);
        setConfirmado(true);
        setModalOpen(false);
    };

    const handleOpenModal = () => {
        setModalOpen(true);
        setConfirmado(false);
    };

    return (
        <div className="p-8 bg-gray-100 min-h-screen font-inter">
            <div className="max-w-4xl mx-auto bg-white rounded-xl shadow-lg p-8">
                <h1 className="text-3xl font-semibold text-gray-800 mb-6">Demonstração do Modal</h1>
                <p className="text-gray-600 mb-4">
                    Clique no botão abaixo para ver o novo modal de confirmação.
                </p>

                <button
                    className="btn btn-primary"
                    onClick={handleOpenModal}
                >
                    Abrir Modal de Confirmação
                </button>

                {confirmado && (
                    <div className="mt-6 p-4 rounded-md bg-green-100 text-green-800">
                        Confirmação realizada com sucesso! Verifique o console para os detalhes.
                    </div>
                )}

            </div>

            <ConfirmacaoModal
                isOpen={modalOpen}
                onClose={() => setModalOpen(false)}
                onConfirm={handleConfirm}
                item={itemExemplo}
                contas={contasExemplo}
            />
        </div>
    );
};

export default App;