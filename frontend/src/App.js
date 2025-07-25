import React from 'react';
import { BrowserRouter as Router, Routes, Route } from 'react-router-dom';
import Login from './pages/login';
import Dashboard from './pages/dashboard';
import Categorias from './pages/categorias';
import Despesas from './pages/despesas';
import Receitas from './pages/receitas';
import Familiares from './pages/familiares';
import Bancos from './pages/bancos';
import MainLayout from './layouts/MainLayout';
import Fornecedores from './pages/fornecedores';
import Usuarios from './pages/usuarios';


function App() {
  return (
        <Router basename="/controleflex">
      <Routes>
        {/* A rota de Login agora será acessada via .../controleflex/ */}
        <Route path="/" element={<Login />} />

        {/* As outras rotas funcionarão normalmente a partir da base */}
        {/* Ex: .../controleflex/dashboard */}
        <Route path="/dashboard" element={<MainLayout><Dashboard /></MainLayout>} />
        <Route path="/categorias" element={<MainLayout><Categorias /></MainLayout>} />
        <Route path="/despesas" element={<MainLayout><Despesas /></MainLayout>} />
        <Route path="/receitas" element={<MainLayout><Receitas /></MainLayout>} />
        <Route path="/familiares" element={<MainLayout><Familiares /></MainLayout>} />
        <Route path="/bancos" element={<MainLayout><Bancos /></MainLayout>} />
        <Route path="/fornecedores" element={<MainLayout><Fornecedores /></MainLayout>} />
        <Route path="/usuarios" element={<MainLayout><Usuarios /></MainLayout>} />
      </Routes>
    </Router>
  );
}

export default App;
