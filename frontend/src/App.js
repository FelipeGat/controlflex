import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';

import Login from './pages/login';
import Home from './components/Home_clima_automatico';
import Dashboard from './pages/dashboard';
import CadastroCategoria from './pages/categorias/CadastroCategoria';
import Despesas from './pages/despesas';
import Receitas from './pages/receitas';
import Familiares from './pages/familiares/familiares.js';
import Bancos from './pages/bancos';
import Fornecedores from './pages/fornecedores';
import Usuarios from './pages/usuarios';
import MainLayout from './layouts/MainLayout';
import SimuladorInvestimentos from './pages/SimuladorInvestimentos';
import Investimentos from './pages/Investimentos';
import Lancamentos from './pages/lancamentos';

// NOVO: Importar a página de escolha de planos
import PaginaEscolhaPlanos from './pages/PaginaEscolhaPlanos';

// NOVO: Importar a tela de sucesso
import TelaSucesso from './pages/TelaSucesso';

function App() {
  return (
    <Router basename="/controleflex">
      <Routes>
        {/* Rota de Login */}
        <Route path="/" element={<Login />} />

        {/* NOVA: Rota para escolha de planos (sem MainLayout pois é página pública) */}
        <Route path="/escolher-plano" element={<PaginaEscolhaPlanos />} />

        {/* NOVA: Rota para tela de sucesso (sem MainLayout pois é página pública) */}
        <Route path="/sucesso" element={<TelaSucesso />} />

        {/* HOME como página inicial após login */}
        <Route path="/home" element={<MainLayout><Home /></MainLayout>} />

        {/* Outras rotas do sistema */}
        <Route path="/dashboard" element={<MainLayout><Dashboard /></MainLayout>} />
        <Route path="/categorias" element={<MainLayout><CadastroCategoria /></MainLayout>} />
        <Route path="/despesas" element={<MainLayout><Despesas /></MainLayout>} />
        <Route path="/receitas" element={<MainLayout><Receitas /></MainLayout>} />
        <Route path="/familiares" element={<MainLayout><Familiares /></MainLayout>} />
        <Route path="/bancos" element={<MainLayout><Bancos /></MainLayout>} />
        <Route path="/fornecedores" element={<MainLayout><Fornecedores /></MainLayout>} />
        <Route path="/usuarios" element={<MainLayout><Usuarios /></MainLayout>} />
        <Route path="/SimuladorInvestimentos" element={<MainLayout><SimuladorInvestimentos /></MainLayout>} />
        <Route path="/lancamentos" element={<MainLayout><Lancamentos /></MainLayout>} />
        <Route path="/Investimentos" element={<MainLayout><Investimentos /></MainLayout>} />
      </Routes>
    </Router>
  );
}

export default App;

