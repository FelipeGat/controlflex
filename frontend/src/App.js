import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';

import Login from './pages/login';
import Dashboard from './pages/dashboard';
import CadastroCategoria from './pages/categorias/CadastroCategoria';
import Despesas from './pages/despesas';
import Receitas from './pages/receitas';
import Familiares from './pages/familiares/familiares.js';
import Bancos from './pages/bancos';
import Fornecedores from './pages/fornecedores';
import Usuarios from './pages/usuarios';
import MainLayout from './layouts/MainLayout';
import Investimentos from './pages/Investimentos';

function App() {
  return (
    <Router basename="/controleflex">
      <Routes>
        <Route path="/" element={<Login />} />
        <Route path="/dashboard" element={<MainLayout><Dashboard /></MainLayout>} />
        <Route path="/categorias" element={<MainLayout><CadastroCategoria /></MainLayout>} />
        <Route path="/despesas" element={<MainLayout><Despesas /></MainLayout>} />
        <Route path="/receitas" element={<MainLayout><Receitas /></MainLayout>} />
        <Route path="/familiares" element={<MainLayout><Familiares /></MainLayout>} />
        <Route path="/bancos" element={<MainLayout><Bancos /></MainLayout>} />
        <Route path="/fornecedores" element={<MainLayout><Fornecedores /></MainLayout>} />
        <Route path="/usuarios" element={<MainLayout><Usuarios /></MainLayout>} />
        <Route path="/investimentos" element={<MainLayout><Investimentos /></MainLayout>} />
      </Routes>
    </Router>
  );
}

export default App;
